<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class RecipeSkill
{
    public function __construct(
        public readonly string $skill,
        public readonly int $level,
        public readonly float $experience,
        public readonly bool $boostable
    ) {
        //
    }
}
