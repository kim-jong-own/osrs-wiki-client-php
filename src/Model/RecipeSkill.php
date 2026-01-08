<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class RecipeSkill
{
    public function __construct(
        public readonly SkillRequirement $requirement,
        public readonly float $experience,
        public readonly bool $boostable
    ) {
        //
    }
}
