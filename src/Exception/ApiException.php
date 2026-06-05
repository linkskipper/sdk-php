<?php

declare(strict_types=1);

namespace LinkSkipper\Exception;

use LinkSkipper\Enum\ErrorCode;
use LinkSkipper\Model\ProblemDetails;

class ApiException extends LinkSkipperException
{
    public function __construct(public readonly ProblemDetails $problem)
    {
        parent::__construct(
            sprintf('%s: %s', $problem->title, $problem->detail),
            $problem->status,
        );
    }

    public function status(): int
    {
        return $this->problem->status;
    }

    public function errorCode(): ErrorCode
    {
        return $this->problem->code;
    }

    public function detail(): string
    {
        return $this->problem->detail;
    }

    public function retryAfter(): ?int
    {
        return $this->problem->retryAfter;
    }

    public function balance(): ?int
    {
        return $this->problem->balance;
    }
}
