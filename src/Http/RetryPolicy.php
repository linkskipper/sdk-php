<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

final class RetryPolicy
{
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $initialDelayMs = 500,
        public readonly int $maxDelayMs = 8000,
        public readonly float $backoffFactor = 2.0,
    ) {
    }

    public function backoffMs(int $attempt, ?int $retryAfterSeconds): int
    {
        if ($retryAfterSeconds !== null) {
            return $retryAfterSeconds * 1000;
        }

        $base = $this->initialDelayMs * ($this->backoffFactor ** ($attempt - 1));
        $capped = (int) min($base, $this->maxDelayMs);

        return intdiv($capped, 2) + random_int(0, max(0, intdiv($capped, 2)));
    }
}
