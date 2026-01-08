<?php

namespace KimJongOwn\OsrsWiki;

use KimJongOwn\OsrsWiki\Enum\Interval;
use KimJongOwn\OsrsWiki\Helpers\TaxHelper;
use KimJongOwn\OsrsWiki\Helpers\Traits\CalculatesAveragePrice;
use KimJongOwn\OsrsWiki\Model\AverageItemPrice;
use KimJongOwn\OsrsWiki\Model\RecipeProfitMargin;
use KimJongOwn\OsrsWiki\Helpers\Traits\CalculatesTickTime;

class RecipeFinder
{
    use CalculatesAveragePrice;
    use CalculatesTickTime;

    public function __construct(
        private Client $client,
    ) {
        //
    }

    /**
     * @return array<int,RecipeProfitMargin>
     */
    public function getRecipeProfitMargins(Interval $interval, ?string $search = null): array
    {
        /** @var array<string,AverageItemPrice> Hourly average price indexed by item id */
        $allPrices = [];
        foreach ($this->client->getAveragePrices($interval) as $price) {
            $allPrices[$price->itemId] = $price;
        }

        $recipes = !empty($search)
            ? $this->client->searchRecipes($search)
            : $this->client->getRecipes();

        $recipeMargins = [];
        foreach ($recipes as $recipe) {
            $productItem = $this->client->findItemByName($recipe->product->item);
            // name may be an alias
            $productItemName = $productItem->name ?? $recipe->product->item;

            $materialItems = [];
            foreach ($recipe->materials as $i => $material) {
                $materialItems[$i] = $this->client->findItemByName($material->item);
            }
            $items = [$productItem, ...$materialItems];
            $averagePrices = [];
            foreach ($items as $item) {
                if ($item === null) {
                    continue;
                }

                $averagePrices[$item->name] = isset($allPrices[$item->id])
                    ? $allPrices[$item->id]
                    : null;
            }

            foreach ($averagePrices as $averagePrice) {
                if ($averagePrice === null) {
                    continue 1;
                }

                $minVolume = !empty($search) ? 10 : 100;
                if (($averagePrice->highPriceVolume + $averagePrice->lowPriceVolume) < $minVolume) {
                    printf("Skipping recipe due to low volume: %s\n", implode('; ', array_filter([$recipe->product->item, $recipe->product->subname, $recipe->product->note])));
                    continue 2; // skip recipe
                }
            }

            $productPrice = isset($averagePrices[$productItemName])
                ? $this->getWeightedAveragePrice($averagePrices[$productItemName], $recipe->product->quantity)
                : 0;

            $materialPrices = [];
            foreach ($recipe->materials as $i => $material) {
                $materialItem = $materialItems[$i] ?? null;
                $fixedPrice = $this->getItemFixedPrice($materialItem->name ?? $material->item, $material->quantity);
                if (isset($fixedPrice)) {
                    $materialPrices[] = $fixedPrice;
                    continue 1;
                }

                $materialAveragePrice = $materialItem && isset($allPrices[$materialItem->id])
                    ? $allPrices[$materialItem->id]
                    : null;

                if ($materialAveragePrice === null) {
                    printf("Skipping recipe due to missing price: %s (%s)\n", implode('; ', array_filter([$recipe->product->item, $recipe->product->subname, $recipe->product->note])), $material->item);
                    continue 2; // skip recipe
                }

                $materialPrices[] = $this->getWeightedAveragePrice($materialAveragePrice, $material->quantity);
            }

            $materialPrice = (int)floor(array_sum($materialPrices));
            $tax = $productPrice
                ? TaxHelper::getInstance()
                    ->calculateTax($productItem->id, (int)floor($productPrice))
                : 0;
            $profit = (int)floor($productPrice - $materialPrice - $tax);

            $recipeMargins[] = new RecipeProfitMargin(
                recipe: $recipe,
                materialPrice: $materialPrice,
                productPrice: $productPrice,
                tax: $tax,
                margin: $profit,
                marginPercent: round($profit / max(1, $materialPrice) * 100, 2),
                margin_per_hour: (int)floor($profit / $this->getTickTimeInHours($recipe->ticks ?: 2)),
                marginPerUnit: (int)floor($profit / $recipe->product->quantity),
                averagePrices: $averagePrices,
            );
        }

        usort(
            $recipeMargins,
            fn (RecipeProfitMargin $a, RecipeProfitMargin $b) => $b->margin_per_hour <=> $a->margin_per_hour
        );

        return $recipeMargins;
    }

    private function getItemFixedPrice(string $itemName, int $quantity): int|null
    {
        $fixedPrices = [
            'Coins' => 1,
            'Platinum token' => 1000,
        ];

        if ($quantity < 50) {
            // Probably a spell: use elemental/combination staff to nullify rune cost
            $fixedPrices = array_merge($fixedPrices, [
                'Earth rune' => 0,
                'Air rune' => 0,
                'Water rune' => 0,
                'Fire rune' => 0,
            ]);
        }

        if (isset($fixedPrices[$itemName])) {
            return (int)floor($fixedPrices[$itemName] * $quantity);
        }

        return null;
    }
}
