<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

final class Account
{
    /**
     * @param list<AccountProvider> $providers
     */
    public function __construct(
        public readonly int $telegramId,
        public readonly int $balance,
        public readonly ?string $subscriptionUntil,
        public readonly array $providers,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawProviders = is_array($payload['providers'] ?? null) ? $payload['providers'] : [];
        $providers = [];
        foreach ($rawProviders as $entry) {
            if (is_array($entry)) {
                $providers[] = AccountProvider::fromArray($entry);
            }
        }

        return new self(
            (int) ($payload['telegram_id'] ?? 0),
            (int) ($payload['balance'] ?? 0),
            isset($payload['subscription_until']) && is_string($payload['subscription_until'])
                ? $payload['subscription_until']
                : null,
            $providers,
        );
    }
}
