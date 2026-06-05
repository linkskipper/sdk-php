<?php

declare(strict_types=1);

namespace LinkSkipper\Enum;

enum ProviderTier: string
{
    case Standard = 'standard';
    case Premium = 'premium';
}
