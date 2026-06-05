<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\ProviderTier;
use LinkSkipper\Enum\ResolveStatus;

final class ResolveResult
{
    public function __construct(
        public readonly ?string $jobId,
        public readonly ResolveStatus $status,
        public readonly ?string $url,
        public readonly ?string $targetUrl,
        public readonly ?string $provider,
        public readonly ?ProviderTier $tier,
        public readonly ?int $creditsCharged,
        public readonly ?bool $cached,
        public readonly ?int $balance,
        public readonly ?int $queuePosition,
        public readonly ?string $pollUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['job_id']) && is_string($payload['job_id']) ? $payload['job_id'] : null,
            ResolveStatus::from((string) ($payload['status'] ?? 'queued')),
            isset($payload['url']) && is_string($payload['url']) ? $payload['url'] : null,
            isset($payload['target_url']) && is_string($payload['target_url']) ? $payload['target_url'] : null,
            isset($payload['provider']) && is_string($payload['provider']) ? $payload['provider'] : null,
            isset($payload['tier']) && is_string($payload['tier']) ? ProviderTier::from($payload['tier']) : null,
            isset($payload['credits_charged']) ? (int) $payload['credits_charged'] : null,
            isset($payload['cached']) ? (bool) $payload['cached'] : null,
            isset($payload['balance']) && $payload['balance'] !== null ? (int) $payload['balance'] : null,
            isset($payload['queue_position']) ? (int) $payload['queue_position'] : null,
            isset($payload['poll_url']) && is_string($payload['poll_url']) ? $payload['poll_url'] : null,
        );
    }

    public function isDone(): bool
    {
        return $this->status === ResolveStatus::Done;
    }
}
