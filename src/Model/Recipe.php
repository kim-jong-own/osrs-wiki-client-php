<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class Recipe
{
    /**
     * @param array<int,RecipeMaterial> $materials
     * @param array<int,RecipeSkill> $skills
     * @param array<int,string> $tools
     * @param array<int,string> $facilities
     */
    public function __construct(
        public readonly RecipeProduct $product,
        public readonly array $materials,
        public readonly array $skills,
        public readonly array $tools,
        public readonly array $facilities,
        public readonly ?int $ticks,
        public readonly bool $members,
    ) {
        //
    }
}
