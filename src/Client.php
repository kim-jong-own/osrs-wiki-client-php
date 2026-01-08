<?php

namespace KimJongOwn\OsrsWiki;

use GuzzleHttp\Client as GuzzleHttpClient;
use KimJongOwn\OsrsWiki\Enum\Interval;
use KimJongOwn\OsrsWiki\Factories\RecipeFactory;
use KimJongOwn\OsrsWiki\Helpers\ItemAliasHelper;
use KimJongOwn\OsrsWiki\Helpers\PotionHelper;
use KimJongOwn\OsrsWiki\Model\AverageItemPrice;
use KimJongOwn\OsrsWiki\Model\BucketQuery;
use KimJongOwn\OsrsWiki\Model\Item;
use KimJongOwn\OsrsWiki\Model\ItemWithPrice;
use KimJongOwn\OsrsWiki\Model\LatestItemPrice;
use KimJongOwn\OsrsWiki\Model\Recipe;

class Client
{
    /**
     * @var array<Recipe>|null $recipes
     */
    private array|null $recipes = null;

    /**
     * @var array<Item>|null $items
     */
    private array|null $items = null;

    public function __construct(
        private GuzzleHttpClient $http,
    ) {
        //
    }

    public function findItemByName(string $name): ?Item
    {
        foreach ($this->getItems() as $item) {
            if (strcasecmp($item->name, $name) === 0) {
                return $item;
            }

            $aliasName = ItemAliasHelper::getInstance()->getItemNameByAlias($name);
            if ($aliasName !== null && strcasecmp($item->name, $aliasName) === 0) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int,Item>
     */
    public function getItems(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $responseData = $this->request('GET', 'https://prices.runescape.wiki/api/v1/osrs/mapping');

        return $this->items = array_map(function ($item) {
            return new Item(
                id: $item['id'],
                name: $item['name'],
                members: $item['members'],
                examine: $item['examine'],
                imageUrl: $this->getFullImageUrl($item['icon']),
                value: $item['value'] ?? null,
                lowAlch: $item['lowalch'] ?? null,
                highAlch: $item['highalch'] ?? null,
                geLimit: $item['limit'] ?? null,
            );
        }, $responseData);
    }

    /**
     * @return ItemWithPrice[]
     */
    public function searchItemsWithPrices(string $search, Interval $interval = Interval::ONE_HOUR): array
    {
        $itemsWithPrices = [];
        $items = [];

        foreach ($this->getItems() as $item) {
            if (stripos($item->name, $search) !== false) {
                $items[$item->id] = $item;
            }
        }

        if ($items) {
            $prices = $this->getAveragePrices($interval);

            foreach ($prices as $price) {
                if (isset($items[$price->itemId])) {
                    $itemsWithPrices[] = new ItemWithPrice(
                        item: $items[$price->itemId],
                        averagePrice: $price,
                    );
                }
            }
        }

        return $itemsWithPrices;
    }

    /**
     * @return array<LatestItemPrice>
     */
    public function getLatestPrices(): array
    {
        $responseData = $this->request('GET', 'https://prices.runescape.wiki/api/v1/osrs/latest');

        return array_map(fn($item, $id) => new LatestItemPrice(
            itemId: $id,
            highPrice: $item['high'],
            highUnixTime: $item['highTime'],
            lowPrice: $item['low'],
            lowUnixTime: $item['lowTime'],
        ), $responseData['data'], array_keys($responseData['data']));
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getAverage5mPrices(): array
    {
        return $this->getAveragePrices(Interval::FIVE_MINS);
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getAverage1hPrices(): array
    {
        return $this->getAveragePrices(Interval::ONE_HOUR);
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getAverage6hPrices(): array
    {
        return $this->getAveragePrices(Interval::SIX_HOURS);
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getAverage24hPrices(): array
    {
        return $this->getAveragePrices(Interval::ONE_DAY);
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getAveragePrices(Interval $interval): array
    {
        $responseData = $this->request(
            'GET',
            sprintf('https://prices.runescape.wiki/api/v1/osrs/%s', urlencode($interval->value))
        );

        return array_map(fn($item, $id) => new AverageItemPrice(
            itemId: $id,
            highPrice: $item['avgHighPrice'],
            highPriceVolume: $item['highPriceVolume'],
            lowPrice: $item['avgLowPrice'],
            lowPriceVolume: $item['lowPriceVolume'],
            unixTime: $responseData['timestamp'],
        ), $responseData['data'], array_keys($responseData['data']));
    }

    /**
     * @return array<AverageItemPrice>
     */
    public function getPriceTimeseries(int $itemId, string $interval = '5m'): array
    {
        $responseData = $this->request(
            'GET',
            sprintf(
                'https://prices.runescape.wiki/api/v1/osrs/timeseries?timestep=%s&id=%d',
                urlencode($interval),
                $itemId,
            )
        );

        return array_map(fn($item) => new AverageItemPrice(
            itemId: $itemId,
            highPrice: $item['avgHighPrice'],
            highPriceVolume: $item['highPriceVolume'],
            lowPrice: $item['avgLowPrice'],
            lowPriceVolume: $item['lowPriceVolume'],
            unixTime: $item['timestamp'],
        ), $responseData['data'], array_keys($responseData['data']));
    }

    /**
     * @return array<Recipe>
     */
    public function getRecipes(): array
    {
        if ($this->recipes !== null) {
            return $this->recipes;
        }

        $requestOptions = [
            'query' => [
                'format' => 'json',
                'action' => 'bucket',
            ],
        ];

        $bucketQuery = new BucketQuery(
            bucket: 'recipe',
            selects: [
                'page_name',
                'page_name_sub',
                'uses_material',
                'uses_tool',
                'uses_facility',
                'is_members_only',
                'is_boostable',
                'uses_skill',
                'source_template',
                'production_json',
            ],
            wheres: [
                ['source_template', 'recipe'],
            ],
            limit: 500,
            offset: 0,
        );

        $buckets = [];

        do {
            $requestOptions['query']['query'] = (string) $bucketQuery;
            $response = $this->request('GET', 'https://oldschool.runescape.wiki/api.php', $requestOptions);
            $buckets = array_merge($buckets, $response['bucket']);
            $bucketQuery->offset += $bucketQuery->limit;
        } while (count($response['bucket']) === $bucketQuery->limit);

        $recipes = array_map(function (array $recipeData) {
            return (new RecipeFactory())->fromArray(json_decode($recipeData['production_json'], true));
        }, $buckets);

        return $this->recipes = array_merge(
            $recipes,
            (new PotionHelper($this->getItems()))->getDecantRecipes(),
        );
    }

    /**
     * @return array<Recipe>
     */
    public function searchRecipes(string $search): array
    {
        return array_values(array_filter(
            $this->getRecipes(),
            function (Recipe $recipe) use ($search) {
                if (stripos($recipe->product->item, $search) !== false) {
                    return true;
                }

                $productAlias = ItemAliasHelper::getInstance()->getItemNameByAlias($recipe->product->item);
                if ($productAlias && stripos($productAlias, $search) !== false) {
                    return true;
                }

                if ($recipe->product->subname && stripos($recipe->product->subname, $search) !== false) {
                    return true;
                }

                if ($recipe->product->note && stripos($recipe->product->note, $search) !== false) {
                    return true;
                }

                foreach ($recipe->materials as $material) {
                    if (stripos($material->item, $search) !== false) {
                        return true;
                    }

                    $materialAlias = ItemAliasHelper::getInstance()->getItemNameByAlias($material->item);
                    if ($materialAlias && stripos($materialAlias, $search) !== false) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        $response = $this->http->request($method, $uri, $this->getRequestOptions($options));

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getRequestOptions(array $options): array
    {
        $defaultOptions = [
            'headers' => [
                'User-Agent' => 'OsrsPricesClient/1.0 - @kim_jong_own',
                'Accept' => 'application/json',
            ],
        ];

        return array_merge_recursive($defaultOptions, $options);
    }

    private function getFullImageUrl(string $iconFile): string
    {
        $encodedIconFile = urlencode(str_replace(' ', '_', $iconFile));
        $fileType = strrchr($encodedIconFile, '.');

        return sprintf(
            'https://oldschool.runescape.wiki/images/%s',
            basename($encodedIconFile, $fileType) . '_detail' . $fileType,
        );
    }
}
