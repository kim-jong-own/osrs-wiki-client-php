<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class RecipeProfitMargin
{
    /**
     * @param array<string,AverageItemPrice|null> $averagePrices
     */
    public function __construct(
        public Recipe $recipe,
        public int $materialPrice,
        public int $productPrice,
        public int $tax,
        public int $margin,
        public float $marginPercent,
        public int $margin_per_hour,
        public int $marginPerUnit,
        public array $averagePrices,
    ) {
        //
    }
}
