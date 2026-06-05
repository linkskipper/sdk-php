<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

use LinkSkipper\Exception\ApiExceptionFactory;
use LinkSkipper\Exception\NetworkException;
use LinkSkipper\Model\ProblemDetails;

final class HttpClient
{
    public function __construct(
        private readonly Transport $transport,
        private readonly RetryPolicy $retryPolicy,
        private readonly Sleeper $sleeper,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeoutMs,
        private readonly string $userAgent,
    ) {
    }

    /**
     * @param array<string, string> $extraHeaders
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
    {
        $request = new HttpRequest(
            $method,
            $this->baseUrl . $path,
            $this->buildHeaders($body !== null, $extraHeaders),
            $body === null ? null : $this->encode($body),
            $this->timeoutMs,
        );

        $response = $this->sendWithRetry($request);

        if ($response->isSuccess()) {
            return $this->decode($response->body);
        }

        throw ApiExceptionFactory::fromProblem($this->toProblem($response));
    }

    private function sendWithRetry(HttpRequest $request): HttpResponse
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = $this->transport->send($request);
            } catch (NetworkException $exception) {
                if ($attempt >= $this->retryPolicy->maxAttempts) {
                    throw $exception;
                }
                $this->sleeper->sleepMs($this->retryPolicy->backoffMs($attempt, null));
                continue;
            }

            if (!$response->isRetriable() || $attempt >= $this->retryPolicy->maxAttempts) {
                return $response;
            }

            $this->sleeper->sleepMs($this->retryPolicy->backoffMs($attempt, $response->retryAfter));
        }
    }

    /**
     * @param array<string, string> $extraHeaders
     *
     * @return array<string, string>
     */
    private function buildHeaders(bool $hasBody, array $extraHeaders): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json, application/problem+json',
            'User-Agent' => $this->userAgent,
        ];
        if ($hasBody) {
            $headers['Content-Type'] = 'application/json';
        }

        return array_merge($headers, $extraHeaders);
    }

    private function toProblem(HttpResponse $response): ProblemDetails
    {
        $payload = $response->body === '' ? [] : json_decode($response->body, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return ProblemDetails::fromResponse($response->status, $payload, $response->retryAfter);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function encode(array $body): string
    {
        $encoded = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return $encoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new NetworkException('The API returned a malformed JSON response.');
        }

        return $decoded;
    }
}
