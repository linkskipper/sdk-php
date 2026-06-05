<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

final class SystemClock implements Clock
{
    public function nowMs(): int
    {
        return (int) (hrtime(true) / 1_000_000);
    }
}
