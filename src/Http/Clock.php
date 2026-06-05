<?php

declare(strict_types=1);

namespace LinkSkipper\Http;

interface Clock
{
    public function nowMs(): int;
}
