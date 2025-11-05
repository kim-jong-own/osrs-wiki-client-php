<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

use Carbon\CarbonImmutable;
use KimJongOwn\OsrsWiki\Helpers\TaxHelper;
use KimJongOwn\OsrsWiki\Helpers\Traits\CalculatesAveragePrice;

class AverageItemPrice
{
    use CalculatesAveragePrice;

    public readonly ?CarbonImmutable $time;
    public readonly ?int $highPriceTax;
    public readonly ?int $margin;
    public readonly ?float $averagePrice;

    public function __construct(
        public readonly int $itemId,
        public readonly ?int $highPrice,
        public readonly ?int $highPriceVolume,
        public readonly ?int $lowPrice,
        public readonly ?int $lowPriceVolume,
        public readonly ?int $unixTime,
    ) {
        $this->time = $unixTime ? CarbonImmutable::createFromTimestamp($unixTime, 'UTC') : null;
        $this->highPriceTax = $highPrice ? (int) TaxHelper::getInstance()->calculateTax($itemId, $highPrice) : null;
        $this->margin = ($highPrice && $lowPrice) ? $highPrice - $lowPrice - $this->highPriceTax : null;
        $this->averagePrice = $this->getWeightedAveragePrice($this, 1);
    }
}
