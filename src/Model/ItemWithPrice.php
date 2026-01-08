<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class ItemWithPrice
{
    public function __construct(
        public Item $item,
        public AverageItemPrice $averagePrice,
    ) {
        //
    }
}
