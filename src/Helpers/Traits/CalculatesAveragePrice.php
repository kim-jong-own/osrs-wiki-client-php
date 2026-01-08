<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Helpers\Traits;

use KimJongOwn\OsrsWiki\Model\AverageItemPrice;

trait CalculatesAveragePrice
{
    protected function getWeightedAveragePrice(AverageItemPrice $price, float $quantity): int
    {
        $totalPrice = 0;
        $totalVolume = 0;

        if ($price->highPrice !== null && $price->highPriceVolume !== null) {
            $totalPrice += ($price->highPrice * $price->highPriceVolume);
            $totalVolume += $price->highPriceVolume;
        }

        if ($price->lowPrice !== null && $price->lowPriceVolume !== null) {
            $totalPrice += ($price->lowPrice * $price->lowPriceVolume);
            $totalVolume += $price->lowPriceVolume;
        }

        return (int) ceil(($totalPrice / $totalVolume) * $quantity);
    }
}
