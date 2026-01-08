<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Helpers;

class ItemAliasHelper
{
    /**
     * Singleton instance.
     */
    private static ItemAliasHelper|null $instance = null;

    /**
     * Array of item names keyed by their alias.
     *
     * @var array<string,string>
     */
    protected array $aliases = [];

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): ItemAliasHelper
    {
        if (self::$instance === null) {
            self::$instance = new ItemAliasHelper();
        }

        return self::$instance;
    }

    public static function setInstance(ItemAliasHelper $itemAliasHelper): void
    {
        self::$instance = $itemAliasHelper;
    }

    public function __construct(string $aliasesFilePath = __DIR__ . '/../Data/ItemAliases.json')
    {
        $this->aliases = json_decode(file_get_contents($aliasesFilePath), true);
    }

    public function getItemNameByAlias(string $alias): string|null
    {
        return $this->aliases[$alias] ?? null;
    }

    /**
     * @return string[]
     */
    public function getAliasByItemName(string $itemName): array
    {
        return array_keys($this->aliases, $itemName);
    }
}
