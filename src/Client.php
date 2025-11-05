<?php

namespace KimJongOwn\OsrsWiki;

use GuzzleHttp\Client as GuzzleHttpClient;
use KimJongOwn\OsrsWiki\Enum\Interval;
use KimJongOwn\OsrsWiki\Model\AverageItemPrice;
use KimJongOwn\OsrsWiki\Model\BucketQuery;
use KimJongOwn\OsrsWiki\Model\Item;
use KimJongOwn\OsrsWiki\Model\LatestItemPrice;
use KimJongOwn\OsrsWiki\Model\Recipe;
use KimJongOwn\OsrsWiki\Model\RecipeMaterial;
use KimJongOwn\OsrsWiki\Model\RecipeProduct;
use KimJongOwn\OsrsWiki\Model\RecipeSkill;

class Client
{
    public function __construct(
        private GuzzleHttpClient $http,
    ) {
        //
    }

    /**
     * @return array<int,Item>
     */
    public function getItems(): array
    {
        $responseData = $this->request('GET', 'https://prices.runescape.wiki/api/v1/osrs/mapping');

        return array_map(function ($item) {
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
        return $this->getAveragePrices(Interval::DAY);
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
        $response = $this->request('GET', 'https://oldschool.runescape.wiki/api.php', [
            'query' => [
                'format' => 'json',
                'action' => 'bucket',
                'query' => (new BucketQuery(
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
                ))->__toString(),
            ],
        ]);

        return array_map(function (array $recipeData) {
            $productionData = json_decode($recipeData['production_json'], true);

            $product = new RecipeProduct(
                item: $productionData['output']['name'],
                quantity: $productionData['output']['quantity'],
                subname: $productionData['output']['subtxt'] ?? null,
                note: $productionData['output']['quantitynote'] ?? null,
            );
            $materials = array_map(function (array $materialData) {
                return new RecipeMaterial(
                    item: $materialData['name'],
                    quantity: (int)$materialData['quantity'],
                );
            }, $productionData['materials'] ?? []);
            $skills = array_map(function (array $skillData) {
                return new RecipeSkill(
                    skill: $skillData['name'],
                    level: (int)$skillData['level'],
                    experience: (float)$skillData['experience'],
                    boostable: isset($skillData['boostable']) && $skillData['boostable'] === 'Yes',
                );
            }, $productionData['skills'] ?? []);

            return new Recipe(
                product: $product,
                materials: $materials,
                skills: $skills,
                tools: isset($productionData['tools']) ? explode(', ', $productionData['tools']) : [],
                facilities: isset($productionData['facilities']) ? explode(', ', $productionData['facilities']) : [],
                ticks: isset($productionData['ticks']) ? (int)$productionData['ticks'] : null,
                members: (bool)$productionData['members'],
            );
        }, $response['bucket']);
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
