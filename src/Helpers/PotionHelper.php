<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Helpers;

use KimJongOwn\OsrsWiki\Model\Item;
use KimJongOwn\OsrsWiki\Model\Recipe;

class PotionHelper
{
    /** @var array<Item> $potions */
    private array $potions;

    /**
     * @param array<Item> $items
     */
    public function __construct(
        array $items,
    ) {
        $this->potions = array_filter($items, fn (Item $item) => $this->isPotion($item));
    }

    public function getPotions(): array
    {
        return $this->potions;
    }

    /**
     * @return array<Recipe>
     */
    public function getDecantRecipes(): array
    {
        $doses = array_reduce(
            $this->potions,
            function (array $carry, Item $item) {
                @[$potion, $dose] = preg_split('/\((1|2|3|4)\)$/', $item->name, 2, PREG_SPLIT_DELIM_CAPTURE);

                if ($dose !== null) {
                    if (!isset($carry[$potion])) {
                        $carry[$potion] = [];
                    }
                    $carry[$potion][$dose] = $item;
                }

                return $carry;
            },
            [],
        );

        return array_reduce(
            $doses,
            function (array $carry, array $potionDoses) {
                $decantRecipes = $this->dosesToDecantRecipes($potionDoses);
                return array_merge($carry, $decantRecipes);
            },
            [],
        );
    }

    /**
     * @param array<int,Item> $potionDoses
     *
     * @return array<Recipe>
     */
    private function dosesToDecantRecipes(array $potionDoses): array
    {
        $possibleDoses = array_keys($potionDoses); // e.g., [1, 2, 3, 4] or [1, 2]

        $recipes = [];

        foreach ($possibleDoses as $materialDose) {
            $productDoses = array_diff($possibleDoses, [$materialDose]);

            $materialPotion = $potionDoses[$materialDose];
            foreach ($productDoses as $productDose) {
                $productPotion = $potionDoses[$productDose];
                $productQuantity = $materialDose / $productDose;

                $recipes[] = new Recipe(
                    product: new \KimJongOwn\OsrsWiki\Model\RecipeProduct(
                        item: $productPotion->name,
                        quantity: $productQuantity,
                        subname: sprintf('Decant %s into %s', $materialPotion->name, $productPotion->name),
                    ),
                    materials: [
                        new \KimJongOwn\OsrsWiki\Model\RecipeMaterial(
                            item: $materialPotion->name,
                            quantity: 1,
                        ),
                    ],
                    skills: [],
                    tools: [],
                    facilities: ['Bob Barter'],
                    ticks: 0,
                    members: true,
                );
            }
        }

        return $recipes;
    }

    private function isPotion(Item $item): bool
    {
        if (0 === preg_match('/\((1|2|3|4)\)$/', $item->name)) {
            return false;
        }

        $prefixBlacklist = [
            'Ring of ',
            'Amulet of ',
            'Necklace of ',
            'Bracelet of ',
            'Rainbow crab',
            'Waterskin',
        ];

        foreach ($prefixBlacklist as $prefix) {
            if (str_starts_with($item->name, $prefix)) {
                return false;
            }
        }

        $keywordBlacklist = [
            ' necklace',
            ' bracelet',
        ];
        foreach ($keywordBlacklist as $keyword) {
            if (str_contains($item->name, $keyword)) {
                return false;
            }
        }

        return true;
    }
}
