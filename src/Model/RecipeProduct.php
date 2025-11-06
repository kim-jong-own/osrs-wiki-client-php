<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

/**
 * The product of a recipe.
 */
class RecipeProduct
{
    public function __construct(
        public readonly string $item,
        public readonly float $quantity,
        public readonly ?string $subname = null,
        public readonly ?string $note = null
    ) {
        //
    }
}
