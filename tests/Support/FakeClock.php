<?php

declare(strict_types=1);

namespace LinkSkipper\Tests\Support;

use LinkSkipper\Http\Clock;

final class FakeClock implements Clock
{
    private int $now;

    private int $step;

    public function __construct(int $start = 0, int $step = 5)
    {
        $this->now = $start;
        $this->step = $step;
    }

    public function nowMs(): int
    {
        $current = $this->now;
        $this->now += $this->step;

        return $current;
    }
}
