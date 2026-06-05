<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

final class SystemSleeper implements Sleeper
{
    public function sleepMs(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
