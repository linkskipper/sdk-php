<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

interface Sleeper
{
    public function sleepMs(int $milliseconds): void;
}
