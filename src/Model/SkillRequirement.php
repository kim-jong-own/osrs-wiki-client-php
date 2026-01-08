<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class SkillRequirement
{
    public function __construct(
        public readonly string $skill,
        public readonly int $level,
    ) {
        //
    }
}
