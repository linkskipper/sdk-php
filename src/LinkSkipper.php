<?php

declare(strict_types=1);

namespace LinkSkipper;

use LinkSkipper\Enum\JobStatus;
use LinkSkipper\Enum\ResolveStatus;
use LinkSkipper\Exception\JobFailedException;
use LinkSkipper\Exception\LinkSkipperException;
use LinkSkipper\Exception\TimeoutException;
use LinkSkipper\Http\HttpClient;
use LinkSkipper\Model\Account;
use LinkSkipper\Model\Job;
use LinkSkipper\Model\ProviderEntry;
use LinkSkipper\Model\ResolvedLink;
use LinkSkipper\Model\ResolveResult;

final class LinkSkipper
{
    private readonly HttpClient $http;

    public function __construct(private readonly Config $config)
    {
        $this->http = new HttpClient(
            $config->transport,
            $config->retryPolicy,
            $config->sleeper,
            $config->baseUrl,
            $config->apiKey,
            $config->timeoutMs,
            $config->userAgent,
        );
    }

    public static function create(string $apiKey, string $baseUrl = Config::DEFAULT_BASE_URL): self
    {
        return new self(new Config($apiKey, $baseUrl));
    }

    public function resolve(string $url, ?string $idempotencyKey = null): ResolveResult
    {
        $headers = $idempotencyKey === null ? [] : ['Idempotency-Key' => $idempotencyKey];
        $payload = $this->http->request('POST', '/v1/resolve', ['url' => $url], $headers);

        return ResolveResult::fromArray($payload);
    }

    public function getJob(string $jobId): Job
    {
        $payload = $this->http->request('GET', '/v1/jobs/' . rawurlencode($jobId));

        return Job::fromArray($payload);
    }

    public function account(): Account
    {
        return Account::fromArray($this->http->request('GET', '/v1/account'));
    }

    /**
     * @return list<ProviderEntry>
     */
    public function providers(): array
    {
        $payload = $this->http->request('GET', '/v1/providers');
        $entries = is_array($payload['providers'] ?? null) ? $payload['providers'] : [];

        $providers = [];
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $providers[] = ProviderEntry::fromArray($entry);
            }
        }

        return $providers;
    }

    public function resolveAndWait(
        string $url,
        ?string $idempotencyKey = null,
        ?int $pollIntervalMs = null,
        ?int $maxWaitMs = null,
    ): ResolvedLink {
        $initial = $this->resolve($url, $idempotencyKey);

        if ($initial->status === ResolveStatus::Done) {
            return ResolvedLink::fromResolveResult($initial);
        }
        if ($initial->jobId === null) {
            throw new LinkSkipperException('The API queued a job without returning a job_id.');
        }

        $budget = $maxWaitMs ?? $this->config->maxWaitMs;
        $interval = $pollIntervalMs ?? $this->config->pollIntervalMs;
        $deadline = $this->config->clock->nowMs() + $budget;
        $jobId = $initial->jobId;

        while (true) {
            $remaining = $deadline - $this->config->clock->nowMs();
            if ($remaining <= 0) {
                throw new TimeoutException($jobId, $budget);
            }

            $this->config->sleeper->sleepMs((int) min($interval, $remaining));
            $job = $this->getJob($jobId);
            $jobId = $job->jobId;

            if (!$job->status->isTerminal()) {
                continue;
            }
            if ($job->status === JobStatus::Done) {
                return ResolvedLink::fromJob($job);
            }

            throw new JobFailedException($job);
        }
    }
}
