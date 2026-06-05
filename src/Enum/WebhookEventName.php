<?php

declare(strict_types=1);

namespace LinkSkipper\Enum;

enum WebhookEventName: string
{
    case ResolveDone = 'resolve.done';
    case ResolveFailed = 'resolve.failed';
}
