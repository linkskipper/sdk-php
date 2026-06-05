<?php

declare(strict_types=1);

namespace LinkSkipper\Enum;

enum JobStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
    case Invalid = 'invalid';

    public function isTerminal(): bool
    {
        return $this === self::Done || $this === self::Failed || $this === self::Invalid;
    }
}
