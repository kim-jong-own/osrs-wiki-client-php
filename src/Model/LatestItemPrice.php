<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

use Carbon\CarbonImmutable;
use KimJongOwn\OsrsWiki\Helpers\TaxHelper;

class LatestItemPrice
{
    public readonly ?CarbonImmutable $highTime;
    public readonly ?CarbonImmutable $lowTime;
    public readonly ?int $highPriceTax;
    public readonly ?int $margin;

    public function __construct(
        public readonly int $itemId,
        public readonly ?int $highPrice,
        public readonly ?int $highUnixTime,
        public readonly ?int $lowPrice,
        public readonly ?int $lowUnixTime,
    ) {
        $this->highTime = $highUnixTime ? CarbonImmutable::createFromTimestamp($highUnixTime, 'UTC') : null;
        $this->lowTime = $lowUnixTime ? CarbonImmutable::createFromTimestamp($lowUnixTime, 'UTC') : null;
        $this->highPriceTax = $highPrice ? (int) TaxHelper::getInstance()->calculateTax($itemId, $highPrice) : null;
        $this->margin = ($highPrice && $lowPrice) ? $highPrice - $lowPrice - $this->highPriceTax : null;
    }
}
