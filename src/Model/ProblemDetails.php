<?php

declare(strict_types=1);

namespace LinkSkipper\Model;

use LinkSkipper\Enum\ErrorCode;

final class ProblemDetails
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly int $status,
        public readonly ErrorCode $code,
        public readonly string $detail,
        public readonly ?int $balance,
        public readonly ?int $retryAfter,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromResponse(int $status, array $payload, ?int $retryAfter): self
    {
        $code = isset($payload['code']) && is_string($payload['code'])
            ? (ErrorCode::tryFrom($payload['code']) ?? ErrorCode::fromStatus($status))
            : ErrorCode::fromStatus($status);

        return new self(
            isset($payload['type']) && is_string($payload['type']) ? $payload['type'] : 'about:blank',
            isset($payload['title']) && is_string($payload['title']) ? $payload['title'] : $code->title(),
            isset($payload['status']) ? (int) $payload['status'] : $status,
            $code,
            isset($payload['detail']) && is_string($payload['detail']) ? $payload['detail'] : $code->title(),
            isset($payload['balance']) && $payload['balance'] !== null ? (int) $payload['balance'] : null,
            $retryAfter,
        );
    }
}
