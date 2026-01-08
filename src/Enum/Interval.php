<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Enum;

enum Interval: string
{
    case FIVE_MINS = '5m';
    case ONE_HOUR = '1h';
    case SIX_HOURS = '6h';
    case ONE_DAY = '24h';
}
