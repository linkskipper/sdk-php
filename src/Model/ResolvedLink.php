<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\ProviderTier;

final class ResolvedLink
{
    public function __construct(
        public readonly ?string $jobId,
        public readonly string $targetUrl,
        public readonly ?string $provider,
        public readonly ?ProviderTier $tier,
        public readonly int $creditsCharged,
        public readonly ?int $balance,
        public readonly bool $cached,
    ) {
    }

    public static function fromResolveResult(ResolveResult $result): self
    {
        return new self(
            $result->jobId,
            (string) $result->targetUrl,
            $result->provider,
            $result->tier,
            $result->creditsCharged ?? 0,
            $result->balance,
            $result->cached ?? false,
        );
    }

    public static function fromJob(Job $job): self
    {
        return new self(
            $job->jobId,
            (string) $job->targetUrl,
            $job->provider,
            $job->tier,
            $job->creditsCharged ?? 0,
            $job->balance,
            false,
        );
    }
}
