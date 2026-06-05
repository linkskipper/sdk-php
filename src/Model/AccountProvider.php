<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\ProviderTier;

final class AccountProvider
{
    public function __construct(
        public readonly string $provider,
        public readonly string $label,
        public readonly ProviderTier $tier,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['provider'] ?? ''),
            (string) ($payload['label'] ?? ''),
            ProviderTier::from((string) ($payload['tier'] ?? 'standard')),
        );
    }
}
