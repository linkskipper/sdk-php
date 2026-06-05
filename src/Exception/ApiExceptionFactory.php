<?php

declare(strict_types=1);

namespace LinkSkipper\Exception;

use LinkSkipper\Enum\ErrorCode;
use LinkSkipper\Model\ProblemDetails;

final class ApiExceptionFactory
{
    /**
     * @var array<string, class-string<ApiException>>
     */
    private const CLASS_BY_CODE = [
        'invalid_request' => InvalidRequestException::class,
        'invalid_key' => InvalidKeyException::class,
        'forbidden_scope' => ForbiddenScopeException::class,
        'unsupported_link' => UnsupportedLinkException::class,
        'link_removed' => LinkRemovedException::class,
        'rate_limited' => RateLimitedException::class,
        'quota_exceeded' => QuotaExceededException::class,
        'out_of_credits' => OutOfCreditsException::class,
        'provider_down' => ProviderDownException::class,
        'resolve_failed' => ResolveFailedException::class,
        'not_found' => NotFoundException::class,
    ];

    public static function fromProblem(ProblemDetails $problem): ApiException
    {
        $class = self::CLASS_BY_CODE[$problem->code->value] ?? ApiException::class;

        return new $class($problem);
    }

    public static function classFor(ErrorCode $code): string
    {
        return self::CLASS_BY_CODE[$code->value] ?? ApiException::class;
    }
}
