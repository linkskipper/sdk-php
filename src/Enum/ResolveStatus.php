<?php

declare(strict_types=1);

namespace LinkSkipper\Enum;

enum ResolveStatus: string
{
    case Done = 'done';
    case Queued = 'queued';
}
