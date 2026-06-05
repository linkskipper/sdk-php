<?php

declare(strict_types=1);

namespace LinkSkipper\Tests\Support;

use LinkSkipper\Http\Sleeper;

final class FakeSleeper implements Sleeper
{
    /**
     * @var list<int>
     */
    public array $slept = [];

    public function sleepMs(int $milliseconds): void
    {
        $this->slept[] = $milliseconds;
    }
}
