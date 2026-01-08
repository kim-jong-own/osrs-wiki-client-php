<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Model;

use Stringable;

/**
 * WikiMedia bucket query representation.
 */
class BucketQuery implements Stringable
{
    /**
     * @param array<int,string> $selects
     * @param array<int,array<string,mixed>> $wheres
     */
    public function __construct(
        public readonly string $bucket,
        public readonly array $selects,
        public readonly array $wheres = [],
        public readonly ?int $limit = null,
        public ?int $offset = null,
    ) {
        //
    }

    public function __toString(): string
    {
        $conditions = [
            $this->getOperation('bucket', $this->bucket),
            $this->getOperation('select', $this->selects),
            ...array_map(fn ($where) => $this->getOperation('where', $where), $this->wheres),
        ];

        if (isset($this->limit)) {
            $conditions[] = $this->getOperation('limit', $this->limit);
        }

        if (isset($this->offset)) {
            $conditions[] = $this->getOperation('offset', $this->offset);
        }

        $conditions[] = $this->getOperation('run');

        return implode('.', $conditions);
    }

    private function getOperation(string $condition, mixed $value = null): string
    {
        $valueString = isset($value)
            ? implode(',', array_map(
                fn($v) => is_numeric($v) ? (string)$v : sprintf("'%s'", urlencode((string)$v)),
                is_array($value) ? $value : [$value]
            ))
            : '';
        return sprintf('%s(%s)', urlencode($condition), $valueString);
    }
}
