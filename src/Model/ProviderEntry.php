<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\ProviderTier;

final class ProviderEntry
{
    /**
     * @param list<string> $hosts
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $label,
        public readonly array $hosts,
        public readonly ProviderTier $tier,
        public readonly string $latency,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawHosts = is_array($payload['hosts'] ?? null) ? $payload['hosts'] : [];
        $hosts = array_values(array_map(static fn ($host): string => (string) $host, $rawHosts));

        return new self(
            (string) ($payload['provider'] ?? ''),
            (string) ($payload['label'] ?? ''),
            $hosts,
            ProviderTier::from((string) ($payload['tier'] ?? 'standard')),
            (string) ($payload['latency'] ?? ''),
        );
    }
}
