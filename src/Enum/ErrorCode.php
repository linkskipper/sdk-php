<?php

declare(strict_types=1);

namespace LinkSkipper\Enum;

enum ErrorCode: string
{
    case InvalidRequest = 'invalid_request';
    case InvalidKey = 'invalid_key';
    case ForbiddenScope = 'forbidden_scope';
    case UnsupportedLink = 'unsupported_link';
    case LinkRemoved = 'link_removed';
    case RateLimited = 'rate_limited';
    case QuotaExceeded = 'quota_exceeded';
    case OutOfCredits = 'out_of_credits';
    case ProviderDown = 'provider_down';
    case ResolveFailed = 'resolve_failed';
    case NotFound = 'not_found';

    public function title(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Invalid request',
            self::InvalidKey => 'Invalid API key',
            self::ForbiddenScope => 'Forbidden scope',
            self::UnsupportedLink => 'Unsupported link',
            self::LinkRemoved => 'Link removed',
            self::RateLimited => 'Rate limited',
            self::QuotaExceeded => 'Daily quota exceeded',
            self::OutOfCredits => 'Out of credits',
            self::ProviderDown => 'Provider unavailable',
            self::ResolveFailed => 'Resolve failed',
            self::NotFound => 'Not found',
        };
    }

    public static function fromStatus(int $status): self
    {
        return match ($status) {
            400 => self::InvalidRequest,
            401 => self::InvalidKey,
            402 => self::OutOfCredits,
            403 => self::ForbiddenScope,
            404 => self::NotFound,
            410 => self::LinkRemoved,
            422 => self::UnsupportedLink,
            429 => self::RateLimited,
            503 => self::ProviderDown,
            default => self::ResolveFailed,
        };
    }
}
