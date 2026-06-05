<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

final class RetryAfter
{
    public static function parse(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return max(0, (int) $header);
        }

        $timestamp = strtotime($header);
        if ($timestamp === false) {
            return null;
        }

        return max(0, (int) ceil($timestamp - time()));
    }
}
