<?php

namespace App\Services;

use App\Models\SearchProfile;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class EbayTaxonomyService
{
    private const LAPTOP_CATEGORY_ID = '175672';

    private const ASPECTS = [
        'brand' => ['Brand'],
        'processor' => ['Processor'],
        'ram_size' => ['RAM Size'],
        'storage_capacity' => ['Storage Capacity', 'Hard Drive Capacity', 'SSD Capacity', 'Storage Type'],
        'screen_size' => ['Screen Size'],
        'gpu' => ['GPU', 'Graphics Processing Type'],
        'operating_system' => ['Operating System'],
    ];

    public function __construct(private EbayApiClient $client) {}

    /**
     * @return array<string, array{aspect_name: string|null, values: list<string>}>
     */
    public function laptopAspectOptions(): array
    {
        $marketplaceId = (string) config('services.ebay.marketplace_id');

        return Cache::remember("ebay.laptop-aspects.v2.{$marketplaceId}", now()->addDay(), function () use ($marketplaceId): array {
            $baseUrl = config('services.ebay.api_base_url').'/commerce/taxonomy/v1';
            $treeId = $this->client->client()
                ->get($baseUrl.'/get_default_category_tree_id', ['marketplace_id' => $marketplaceId])
                ->throw()
                ->json('categoryTreeId');

            if (! is_string($treeId) || $treeId === '') {
                throw new RuntimeException('eBay did not return a category tree for the configured marketplace.');
            }

            $subtree = $this->client->client()
                ->get($baseUrl.'/category_tree/'.$treeId.'/get_category_subtree', [
                    'category_id' => self::LAPTOP_CATEGORY_ID,
                ])
                ->throw()
                ->json('categorySubtreeNode');

            if (! is_array($subtree)) {
                throw new RuntimeException('eBay returned an invalid laptop category response.');
            }

            $leafCategoryIds = collect($subtree['childCategoryTreeNodes'] ?? [])
                ->filter(fn (mixed $node): bool => is_array($node) && ($node['leafCategoryTreeNode'] ?? false))
                ->map(fn (array $node): string => (string) data_get($node, 'category.categoryId'))
                ->filter(fn (string $id): bool => $id !== '')
                ->values();

            if ($leafCategoryIds->isEmpty()) {
                throw new RuntimeException('eBay did not return any laptop subcategories with filter options.');
            }

            $aspects = $leafCategoryIds->flatMap(function (string $categoryId) use ($baseUrl, $treeId): array {
                $response = $this->client->client()
                    ->get($baseUrl.'/category_tree/'.$treeId.'/get_item_aspects_for_category', [
                        'category_id' => $categoryId,
                    ])
                    ->throw()
                    ->json('aspects', []);

                if (! is_array($response)) {
                    throw new RuntimeException('eBay returned invalid laptop aspect options.');
                }

                return array_filter($response, 'is_array');
            });

            $byName = $aspects
                ->groupBy(fn (array $aspect): string => mb_strtolower((string) ($aspect['localizedAspectName'] ?? '')));

            return collect(self::ASPECTS)->mapWithKeys(function (array $aliases, string $field) use ($byName): array {
                $matchingAspects = collect($aliases)
                    ->flatMap(fn (string $alias) => $byName->get(mb_strtolower($alias), collect()))
                    ->filter(fn (mixed $aspect): bool => is_array($aspect));

                if ($matchingAspects->isEmpty()) {
                    return [$field => ['aspect_name' => null, 'values' => []]];
                }

                $values = $matchingAspects
                    ->flatMap(fn (array $aspect): array => $aspect['aspectValues'] ?? [])
                    ->filter(fn (mixed $value): bool => is_array($value) && is_string($value['localizedValue'] ?? null))
                    ->map(fn (array $value): string => $value['localizedValue'])
                    ->filter(fn (string $value): bool => $value !== '')
                    ->unique()
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                $aspectName = (string) $matchingAspects->first()['localizedAspectName'];

                return [$field => [
                    'aspect_name' => $aspectName,
                    'values' => $values,
                ]];
            })->all();
        });
    }

    public function aspectFilterForProfile(SearchProfile $profile): ?string
    {
        $filters = $this->aspectFiltersForProfile($profile);

        if ($filters === []) {
            return null;
        }

        return 'categoryId:'.self::LAPTOP_CATEGORY_ID.','.collect($filters)->pluck('filter')->join(',');
    }

    /**
     * @return array<string, array{label: string, value: string, filter: string}>
     */
    public function aspectFiltersForProfile(SearchProfile $profile): array
    {
        $hasAspectValue = collect(array_keys(self::ASPECTS))
            ->contains(fn (string $field): bool => $this->profileAspectValues($profile, $field) !== []);

        if (! $hasAspectValue) {
            return [];
        }

        $options = $this->laptopAspectOptions();
        $filters = [];

        foreach (array_keys(self::ASPECTS) as $field) {
            $values = $this->profileAspectValues($profile, $field);
            if ($values === []) {
                continue;
            }

            $aspectName = $options[$field]['aspect_name'] ?? null;
            if ($aspectName === null) {
                throw new RuntimeException("The selected eBay value for {$field} is no longer available in this category.");
            }

            foreach ($values as $value) {
                if (! in_array($value, $options[$field]['values'], true)) {
                    throw new RuntimeException("The selected eBay value for {$field} is no longer available in this category.");
                }
            }

            $label = match ($field) {
                'brand' => 'Бренд',
                'processor' => 'Процессор',
                'ram_size' => 'Оперативная память',
                'storage_capacity' => 'Накопитель',
                'screen_size' => 'Размер экрана',
                'gpu' => 'Видеокарта',
                'operating_system' => 'Операционная система',
                default => $field,
            };
            $filters[$field] = [
                'label' => $label,
                'value' => implode(', ', $values),
                'filter' => $aspectName.':{'.implode('|', $values).'}',
            ];
        }

        return $filters;
    }

    /**
     * @return list<string>
     */
    private function profileAspectValues(SearchProfile $profile, string $field): array
    {
        $values = match ($field) {
            'brand' => $profile->brands ?: [$profile->brand],
            'processor' => $profile->processors ?: [$profile->processor],
            'ram_size' => $profile->ram_sizes ?: [
                $profile->ram_size ?: ($profile->min_ram_gb ? $profile->min_ram_gb.' GB' : null),
            ],
            'storage_capacity' => $profile->storage_capacities ?: [$profile->storage_capacity],
            'screen_size' => $profile->screen_sizes ?: [$profile->screen_size],
            'gpu' => $profile->gpus ?: [$profile->gpu],
            'operating_system' => [$profile->operating_system],
            default => [],
        };

        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }
}
