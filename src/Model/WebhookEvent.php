<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\JobStatus;
use LinkSkipper\Enum\ProviderTier;
use LinkSkipper\Enum\WebhookEventName;

final class WebhookEvent
{
    public function __construct(
        public readonly WebhookEventName $event,
        public readonly string $createdAt,
        public readonly string $jobId,
        public readonly JobStatus $status,
        public readonly ?string $targetUrl,
        public readonly ?string $provider,
        public readonly ?ProviderTier $tier,
        public readonly ?string $error,
        public readonly int $creditsCharged,
        public readonly ?int $balance,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            WebhookEventName::from((string) ($payload['event'] ?? '')),
            (string) ($payload['created_at'] ?? ''),
            (string) ($payload['job_id'] ?? ''),
            JobStatus::from((string) ($payload['status'] ?? 'queued')),
            isset($payload['target_url']) && is_string($payload['target_url']) ? $payload['target_url'] : null,
            isset($payload['provider']) && is_string($payload['provider']) ? $payload['provider'] : null,
            isset($payload['tier']) && is_string($payload['tier']) ? ProviderTier::from($payload['tier']) : null,
            isset($payload['error']) && is_string($payload['error']) ? $payload['error'] : null,
            isset($payload['credits_charged']) ? (int) $payload['credits_charged'] : 0,
            isset($payload['balance']) && $payload['balance'] !== null ? (int) $payload['balance'] : null,
        );
    }
}
