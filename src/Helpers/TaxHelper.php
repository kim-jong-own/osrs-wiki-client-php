<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Helpers;

class TaxHelper
{
    /**
     * Singleton instance.
     */
    private static TaxHelper|null $instance = null;

    public const TAX_RATE = 0.02;

    /**
     * Array of exempt items keyed by their id.
     *
     * @var array<int,string>
     */
    protected array $exemptItems = [];

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): TaxHelper
    {
        if (self::$instance === null) {
            self::$instance = new TaxHelper();
        }

        return self::$instance;
    }

    public static function setInstance(TaxHelper $taxHelper): void
    {
        self::$instance = $taxHelper;
    }

    public function __construct(string $exeptionFilePath = __DIR__ . '/../Data/TaxExemptItems.json')
    {
        $this->exemptItems = json_decode(file_get_contents($exeptionFilePath), true);
    }

    /**
     * Calculate the tax for a given item and amount.
     */
    public function calculateTax(int $itemId, int $amount): int
    {
        if ($this->isExempt($itemId)) {
            return 0;
        }

        return (int) floor($amount * self::TAX_RATE);
    }

    /**
     * Check if an item is tax exempt.
     */
    public function isExempt(int $itemId): bool
    {
        return isset($this->exemptItems[$itemId]);
    }
}
