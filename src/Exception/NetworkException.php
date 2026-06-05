<?php

declare(strict_types=1);

namespace LinkSkipper\Exception;

use Throwable;

final class NetworkException extends LinkSkipperException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
