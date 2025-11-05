<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

class Item
{
    public function __construct(
        public readonly int $id,
        public readonly bool $members,
        public readonly string $name,
        public readonly string $examine,
        public readonly string $imageUrl,
        public readonly ?int $value,
        public readonly ?int $lowAlch,
        public readonly ?int $highAlch,
        public readonly ?int $geLimit,
    ) {
        //
    }
}
