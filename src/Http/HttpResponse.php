<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly ?int $retryAfter,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isRetriable(): bool
    {
        return $this->status === 429 || $this->status >= 500;
    }
}
