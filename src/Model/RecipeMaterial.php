<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

/**
 * A material required for a recipe.
 */
class RecipeMaterial
{
    public function __construct(
        public readonly string $item,
        public readonly int $quantity,
    ) {
        //
    }
}
