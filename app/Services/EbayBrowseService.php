<?php

namespace App\Services;

use App\Models\ExcludedWord;
use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Models\SiteSetting;
use App\Notifications\NewEbayListingNotification;
use App\Notifications\SharedEbayListingNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EbayBrowseService
{
    private const LAPTOP_CATEGORY_ID = '175672';

    private const DEFAULT_PAGE_SIZE = 200;

    private const DEFAULT_MAX_RESULTS = 10000;

    private int $filterCheckCallCount = 0;

    private int $pageImportCallCount = 0;

    public function __construct(
        private EbayApiClient $client,
        private EbayTaxonomyService $taxonomy,
        private TelegramNotificationService $telegram,
        private ListingSpecificationService $listingSpecifications,
    ) {}

    private function getPageSize(): int
    {
        return (int) (SiteSetting::query()->value('import_page_size') ?? self::DEFAULT_PAGE_SIZE);
    }

    private function getMaxResults(): int
    {
        return (int) (SiteSetting::query()->value('import_max_results') ?? self::DEFAULT_MAX_RESULTS);
    }

    private function getCategoryId(): string
    {
        return (string) (SiteSetting::query()->value('ebay_category_id') ?? self::LAPTOP_CATEGORY_ID);
    }

    private function getSortBy(): string
    {
        return (string) (SiteSetting::query()->value('ebay_sort_by') ?? 'price');
    }

    /**
     * @return array<string>
     */
    private function getIgnoredSellers(): array
    {
        $value = SiteSetting::query()->value('ebay_ignored_sellers');
        if ($value === null) {
            return [];
        }

        $sellers = json_decode($value, true);
        if (! is_array($sellers)) {
            return [];
        }

        return collect($sellers)
            ->map(fn (string $seller) => mb_strtolower(trim($seller)))
            ->filter()
            ->values()
            ->all();
    }

    private function isSellerIgnored(string $sellerName): bool
    {
        $ignored = $this->getIgnoredSellers();
        if ($ignored === []) {
            return false;
        }

        $lowerName = mb_strtolower(trim($sellerName));

        return in_array($lowerName, $ignored, true);
    }

    /**
     * @return array{imported: int, new_listings: int}
     */
    public function import(SearchProfile $profile): array
    {
        $imported = 0;
        $found = null;
        $newListings = 0;
        $unprocessedCount = 0;
        $segments = [[
            'offset' => 0,
            'price_min_cents' => null,
            'price_max_cents' => null,
        ]];
        $runId = (string) Str::uuid();
        $this->recordActivity(
            $profile,
            'parse_started',
            "Запущен парсинг профиля «{$profile->name}».",
            ['query' => $profile->query],
            $runId,
            getmypid(),
        );

        // Record the start time to check against the stuck threshold
        $profileStartTime = Carbon::now('Asia/Bishkek');

        try {
            $this->pageImportCallCount = 0;

            // Add an initial delay before the first request to reduce the chance of hitting rate limits
            // when multiple profiles run in sequence
            $initialDelay = SiteSetting::query()->value('ebay_profile_delay_seconds') ?? config('services.ebay.profile_delay_seconds', 5);
            if ((int) $initialDelay > 0 && $this->pageImportCallCount === 0) {
                Log::info("Initial delay before import: {$initialDelay}s for profile {$profile->name}.");
                sleep((int) $initialDelay);
            }

            while ($segments !== []) {
                // Check if we have reached the daily API limit threshold
                if ($this->hasReachedApiLimit()) {
                    $usage = $this->getDailyApiUsage();
                    $this->recordActivity(
                        $profile,
                        'parse_killed',
                        "Парсинг профиля «{$profile->name}» остановлен: достигнут порог дневного лимита eBay API ({$usage['used']}/{$usage['limit']} запросов).",
                        ['imported' => $imported, 'offset' => $offset, 'reason' => 'api_limit_reached', 'api_used' => $usage['used']],
                        $runId,
                        getmypid(),
                    );

                    throw new RuntimeException("Парсинг остановлен: достигнут порог дневного лимита eBay API ({$usage['used']}/{$usage['limit']} запросов).");
                }

                // Check if this profile has exceeded the stuck threshold
                if ($this->isProfileStuck($profile, $profileStartTime)) {
                    $this->recordActivity(
                        $profile,
                        'parse_killed',
                        "Парсинг профиля «{$profile->name}» закрыт принудительно: превышен порог времени ({$imported} товаров обработано на оффсете {$offset}).",
                        ['imported' => $imported, 'offset' => $offset, 'reason' => 'timeout'],
                        $runId,
                        getmypid(),
                    );

                    throw new RuntimeException("Профиль «{$profile->name}» закрыт принудительно: превышен порог времени.");
                }

                $segment = array_shift($segments);
                $offset = $segment['offset'];
                $done = false;

                while (! $done) {
                    // Add delay between page requests to avoid eBay API rate limiting
                    if ($this->pageImportCallCount > 0) {
                        $delay = SiteSetting::query()->value('ebay_page_delay_seconds') ?? config('services.ebay.page_delay_seconds', 5);
                        if ((int) $delay > 0) {
                            Log::info("Page import rate limit delay: {$delay}s (page #{$this->pageImportCallCount}).");
                            sleep((int) $delay);
                        }
                    } else {
                        // First page also gets a small delay to avoid immediate rate limit after profile start
                        $firstPageDelay = SiteSetting::query()->value('ebay_page_delay_seconds') ?? config('services.ebay.page_delay_seconds', 5);
                        if ((int) $firstPageDelay > 0) {
                            Log::info("First page delay: {$firstPageDelay}s.");
                            sleep((int) $firstPageDelay);
                        }
                    }
                    $this->pageImportCallCount++;

                    $page = $this->importPage(
                        $profile,
                        $offset,
                        $segment['price_min_cents'],
                        $segment['price_max_cents'],
                    );
                    $found ??= $page['found'];
                    $imported += $page['imported'];
                    $newListings += $page['new_listings'];
                    $unprocessedCount += $page['unprocessed_count'];
                    array_push($segments, ...$page['next_partitions']);
                    $done = $page['done'];
                    $offset = $page['next_offset'];
                }
            }

            if ($unprocessedCount > 0) {
                throw new RuntimeException("eBay left {$unprocessedCount} results unprocessed because more than ".self::DEFAULT_MAX_RESULTS.' listings share the same price.');
            }
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            // Only record parse_failed if it's not a timeout kill (already recorded as parse_killed)
            if (! str_contains($exception->getMessage(), 'закрыт принудительно')) {
                $this->recordActivity(
                    $profile,
                    'parse_failed',
                    "Ошибка парсинга профиля «{$profile->name}» на странице с отступом {$offset}.",
                    ['offset' => $offset],
                    $runId,
                    getmypid(),
                );
            }

            throw $exception;
        }

        $profile->update(['last_synced_at' => now()]);
        $this->recordActivity(
            $profile,
            'parse_completed',
            "Парсинг профиля «{$profile->name}» завершён: найдено {$found}, сохранено или обновлено {$imported} товаров.",
            ['found' => $found ?? 0, 'imported' => $imported, 'new_listings' => $newListings],
            $runId,
            getmypid(),
        );

        return ['imported' => $imported, 'new_listings' => $newListings];
    }

    /**
     * Check if the profile import has exceeded the stuck threshold.
     */
    private function isProfileStuck(SearchProfile $profile, Carbon $profileStartTime): bool
    {
        $thresholdMinutes = SiteSetting::query()->value('ebay_stuck_threshold_minutes') ?? 180;
        $elapsedMinutes = $profileStartTime->diffInMinutes(Carbon::now('Asia/Bishkek'));

        if ($elapsedMinutes >= (int) $thresholdMinutes) {
            Log::warning("Profile {$profile->name} exceeded stuck threshold ({$elapsedMinutes} >= {$thresholdMinutes} minutes).", [
                'profile_id' => $profile->id,
                'elapsed_minutes' => $elapsedMinutes,
                'threshold_minutes' => $thresholdMinutes,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @return array{
     *     baseline_count: int,
     *     filtered_count: int,
     *     filter_impacts: list<array{label: string, value: string, result_count: int, reduction: int, remaining_percent: ?float}>,
     *     recommendation: ?string,
     *     existing_count: int,
     *     new_count: int,
     *     excluded_count: int,
     *     sample_listings: list<array{title: string, price: float, currency: string, item_url: string, location: string, location_url: string}>,
     * }
     */
    public function analyzeFilterImpact(SearchProfile $profile): array
    {
        // Reset the call counter for a fresh filter check session
        $this->filterCheckCallCount = 0;

        // Add initial delay before starting filter checks to avoid rate limiting
        $initialDelay = SiteSetting::query()->value('ebay_filter_check_delay_seconds') ?? config('services.ebay.filter_check_delay_seconds', 3);
        Log::info("Filter check initial delay: {$initialDelay}s.");
        sleep((int) $initialDelay);

        $categoryId = $this->getCategoryId();
        $baseParameters = [
            'q' => $profile->query,
            'limit' => 1,
            'category_ids' => $categoryId,
        ];
        $baselineCount = $this->searchResultCountWithDelay($baseParameters, quickFail: true);
        $criteria = [];

        if ($profile->condition) {
            $criteria[] = [
                'label' => 'Состояние',
                'value' => match ($profile->condition) {
                    'NEW' => 'Новое',
                    'USED' => 'Б/у',
                    'REFURBISHED' => 'Refurbished',
                    default => $profile->condition,
                },
                'parameters' => ['filter' => 'conditions:{'.$profile->condition.'}'],
            ];
        }

        foreach ([
            'min_price' => ['label' => 'Минимальная цена', 'value' => $profile->min_price],
            'max_price' => ['label' => 'Максимальная цена', 'value' => $profile->max_price],
        ] as $field => $definition) {
            if ($definition['value'] === null) {
                continue;
            }

            $range = $field === 'min_price'
                ? 'price:['.$definition['value'].'..]'
                : 'price:[0..'.$definition['value'].']';
            $criteria[] = [
                'label' => $definition['label'],
                'value' => 'USD '.number_format((float) $definition['value'], 2, '.', ''),
                'parameters' => ['filter' => $range.',priceCurrency:USD'],
            ];
        }

        $aspectFilters = $this->taxonomy->aspectFiltersForProfile($profile);
        foreach ($aspectFilters as $field => $aspectFilter) {
            $criteria[] = [
                'label' => $aspectFilter['label'],
                'value' => $aspectFilter['value'],
                'parameters' => [
                    'aspect_filter' => 'categoryId:'.$categoryId.','.$aspectFilter['filter'],
                ],
            ];
        }

        $filterImpacts = [];
        foreach ($criteria as $criterion) {
            $resultCount = $this->searchResultCountWithDelay([
                ...$baseParameters,
                ...$criterion['parameters'],
            ], quickFail: true);
            $filterImpacts[] = [
                'label' => $criterion['label'],
                'value' => $criterion['value'],
                'result_count' => $resultCount,
                'reduction' => max(0, $baselineCount - $resultCount),
                'remaining_percent' => $baselineCount > 0
                    ? min(100, round($resultCount / $baselineCount * 100, 1))
                    : null,
            ];
        }

        $filterImpacts = collect($filterImpacts)
            ->sortByDesc('reduction')
            ->values()
            ->all();
        $combinedParameters = [
            ...$baseParameters,
        ];
        $combinedFilter = $this->filters($profile);
        if ($combinedFilter !== '') {
            $combinedParameters['filter'] = $combinedFilter;
        }
        $combinedAspectFilter = $aspectFilters === []
            ? null
            : 'categoryId:'.$categoryId.','.collect($aspectFilters)->pluck('filter')->join(',');
        if ($combinedAspectFilter !== null) {
            $combinedParameters['aspect_filter'] = $combinedAspectFilter;
        }
        $filteredCount = $criteria === []
            ? $baselineCount
            : $this->searchResultCountWithDelay($combinedParameters, quickFail: true);
        $recommendation = $this->filterRecommendation($baselineCount, $filteredCount, $filterImpacts);

        // Fetch sample item IDs to estimate existing vs new vs excluded listings
        [$existingCount, $newCount, $excludedCount, $sampleListings] = $this->estimateExistingAndNewListings($profile, $combinedParameters);

        return [
            'baseline_count' => $baselineCount,
            'filtered_count' => $filteredCount,
            'filter_impacts' => $filterImpacts,
            'recommendation' => $recommendation,
            'existing_count' => $existingCount,
            'new_count' => $newCount,
            'excluded_count' => $excludedCount,
            'sample_listings' => $sampleListings,
        ];
    }

    /**
     * Fetch a sample of item IDs from eBay and compare with the database
     * to estimate how many listings already exist, will be new, and will be excluded.
     * Also returns sample listings data for display in a table.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{0: int, 1: int, 2: int, 3: list<array{title: string, price: float, currency: string, item_url: string, location: string, location_url: string}>} [existing_count, new_count, excluded_count, sample_listings]
     */
    private function estimateExistingAndNewListings(SearchProfile $profile, array $parameters): array
    {
        $user = $profile->user;
        if ($user === null) {
            return [0, 0, 0, []];
        }

        // Get the real count of existing listings for this profile from the database
        $existingCount = LaptopListing::query()
            ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.id', $profile->id))
            ->whereNull('laptop_listings.deleted_at')
            ->count();

        // Fetch up to 200 items to estimate new and excluded listings
        $sampleParameters = [
            ...$parameters,
            'limit' => 200,
            'offset' => 0,
        ];

        try {
            $delay = SiteSetting::query()->value('ebay_filter_check_delay_seconds') ?? config('services.ebay.filter_check_delay_seconds', 3);
            sleep((int) $delay);

            $response = $this->client->client()->get(
                config('services.ebay.api_base_url').'/buy/browse/v1/item_summary/search',
                $sampleParameters,
            );

            if ($response->status() === 429) {
                // Rate limited — skip the estimate
                return [$existingCount, 0, 0, []];
            }

            $items = $response->json('itemSummaries', []);
            if (! is_array($items) || $items === []) {
                return [$existingCount, 0, 0, []];
            }

            // Filter items with valid item IDs
            $validItems = collect($items)
                ->filter(fn (mixed $item): bool => is_array($item)
                    && is_string($item['itemId'] ?? null)
                    && $item['itemId'] !== '')
                ->values();

            if ($validItems->isEmpty()) {
                return [$existingCount, 0, 0, []];
            }

            $ebayItemIds = $validItems->pluck('itemId')->unique()->values()->all();

            // Get existing item IDs in the sample to exclude them from new count
            $existingItemIdsInSample = LaptopListing::withTrashed()
                ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $user->id))
                ->whereIn('ebay_item_id', $ebayItemIds)
                ->where(fn (Builder $query) => $query->whereNull('sold_at')->where('status', '<>', 'buy'))
                ->pluck('ebay_item_id')
                ->all();

            $existingItemIdsSet = array_flip($existingItemIdsInSample);

            // Check new items against all the same filters used during import
            $excludedCount = 0;
            $newItemCountForDisplay = 0;
            $sampleListings = [];

            foreach ($validItems as $item) {
                $title = is_string($item['title'] ?? null) ? $item['title'] : '';
                $itemUrl = is_string($item['itemWebUrl'] ?? null) ? $item['itemWebUrl'] : '';
                $price = (float) data_get($item, 'price.value', data_get($item, 'totalPrice.value', 0));
                $currency = is_string(data_get($item, 'price.currency', $item['currency'] ?? 'USD'))
                    ? data_get($item, 'price.currency', $item['currency'] ?? 'USD')
                    : 'USD';

                // Skip items that already exist in the database
                if (isset($existingItemIdsSet[$item['itemId']])) {
                    $sampleListings[] = [
                        'title' => $title,
                        'price' => $price,
                        'currency' => $currency,
                        'item_url' => $itemUrl,
                        'location' => 'Список товаров',
                        'location_url' => '/listings?search='.rawurlencode($title).'&search_in=title',
                    ];

                    continue;
                }

                // Skip unavailable items (out of stock, ended)
                if ($this->isUnavailable($item)) {
                    continue;
                }

                // Skip ignored sellers
                $sellerName = data_get($item, 'seller.username');
                if (is_string($sellerName) && $this->isSellerIgnored($sellerName)) {
                    continue;
                }

                // Get localized aspects for processor check
                $aspects = collect($item['localizedAspects'] ?? [])
                    ->mapWithKeys(fn (array $aspect): array => [mb_strtolower($aspect['name']) => $aspect['value']])
                    ->all();

                // Skip accessories and items that don't match selected processor
                if (
                    $this->listingSpecifications->isAccessoryTitle($title)
                    || ! $this->listingSpecifications->matchesSelectedProcessor($profile, $title, $aspects)
                ) {
                    continue;
                }

                // This is a new item — check if it would be excluded by excluded words
                $sellerDescription = is_string($item['sellerDescription'] ?? null) ? $item['sellerDescription'] : '';

                if ($this->checkExcludedWords($title, $sellerDescription) !== null) {
                    $excludedCount++;
                    $sampleListings[] = [
                        'title' => $title,
                        'price' => $price,
                        'currency' => $currency,
                        'item_url' => $itemUrl,
                        'location' => 'Исключённый товар',
                        'location_url' => '/listings/excluded?search='.rawurlencode($title).'&search_in=title',
                    ];
                } else {
                    $newItemCountForDisplay++;
                    $sampleListings[] = [
                        'title' => $title,
                        'price' => $price,
                        'currency' => $currency,
                        'item_url' => $itemUrl,
                        'location' => 'Список товаров',
                        'location_url' => '/listings?search='.rawurlencode($title).'&search_in=title',
                    ];
                }
            }

            return [$existingCount, $newItemCountForDisplay, $excludedCount, $sampleListings];
        } catch (ConnectionException|RequestException $exception) {
            // If the request fails, skip the estimate
            Log::warning('Failed to fetch sample items for filter analysis.', [
                'profile' => $profile->name,
                'error' => $exception->getMessage(),
            ]);

            return [$existingCount, 0, 0, []];
        }
    }

    /**
     * @param  int|null  $priceMinCents  Inclusive lower bound for a continuation partition.
     * @param  int|null  $priceMaxCents  Inclusive upper bound for a continuation partition.
     * @return array{found: int, processable: int, processed: int, page_processed: int, page_item_ids: list<string>, imported: int, new_listings: int, next_offset: int, done: bool, next_partitions: list<array{offset: int, price_min_cents: ?int, price_max_cents: ?int}>, unprocessed_count: int, saved_listings: list<array{title: string, price: float, currency: string, item_url: string, location: string, location_url: string}>}
     */
    public function importPage(
        SearchProfile $profile,
        int $offset,
        ?int $priceMinCents = null,
        ?int $priceMaxCents = null,
    ): array {
        $maxResults = $this->getMaxResults();
        $pageSize = $this->getPageSize();

        if ($offset < 0 || $offset >= $maxResults) {
            throw new RuntimeException('The eBay search offset is outside the supported result range.');
        }

        $limit = min($pageSize, $maxResults - $offset);
        $parameters = $this->searchParameters($profile, $offset, $priceMinCents, $priceMaxCents);
        $parameters['limit'] = $limit;
        $response = $this->fetchWithRetry($parameters);
        $found = $response->json('total');
        $items = $response->json('itemSummaries', []);

        if (! is_int($found) || $found < 0 || ! is_array($items)) {
            throw new RuntimeException('eBay returned an invalid laptop search response.');
        }

        $processable = min($found, $maxResults);
        if ($items === [] && $offset < $processable) {
            throw new RuntimeException('eBay returned no listings before the end of the search results.');
        }

        if (count($items) < $limit && $offset + count($items) < $processable) {
            Log::warning('eBay returned an incomplete page; stopping import gracefully.', [
                'profile' => $profile->name,
                'offset' => $offset,
                'items_received' => count($items),
                'expected' => $limit,
                'processable' => $processable,
            ]);

            // Process whatever items we received and stop gracefully
            $found = count($items);
            $processable = count($items);
        }

        $newListings = 0;
        $imported = 0;
        $savedListings = [];
        $newListingsForNotification = [];
        $profileMatchesForNotification = [];
        $sharedPriceChangesForNotification = [];
        $priceDropsForNotification = [];
        $user = $profile->user;
        $ebayItemIds = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item)
                && is_string($item['itemId'] ?? null)
                && $item['itemId'] !== '')
            ->pluck('itemId')
            ->unique()
            ->values()
            ->all();
        $soldEbayItemIds = $ebayItemIds === []
            || $user === null
            ? []
            : LaptopListing::withTrashed()
                ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $user->id))
                ->whereIn('ebay_item_id', $ebayItemIds)
                ->where(fn (Builder $query) => $query->whereNotNull('sold_at')->orWhere('status', 'buy'))
                ->pluck('ebay_item_id')
                ->flip()
                ->all();
        $existingListings = $ebayItemIds === []
            ? collect()
            : LaptopListing::withTrashed()
                ->where('search_profile_id', $profile->id)
                ->whereIn('ebay_item_id', $ebayItemIds)
                ->get(['ebay_item_id', 'seller_description', 'raw_payload'])
                ->keyBy('ebay_item_id');

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['itemId'], $item['title'], $item['itemWebUrl'])) {
                throw new RuntimeException('eBay returned a laptop listing with missing required fields.');
            }

            if (isset($soldEbayItemIds[$item['itemId']])) {
                continue;
            }

            if ($this->isUnavailable($item)) {
                continue;
            }

            // Check if seller is in the ignored list
            $sellerName = data_get($item, 'seller.username');
            if (is_string($sellerName) && $this->isSellerIgnored($sellerName)) {
                continue;
            }

            $aspects = collect($item['localizedAspects'] ?? [])
                ->mapWithKeys(fn (array $aspect): array => [mb_strtolower($aspect['name']) => $aspect['value']])
                ->all();

            if (
                $this->listingSpecifications->isAccessoryTitle($item['title'])
                || ! $this->listingSpecifications->matchesSelectedProcessor($profile, $item['title'], $aspects)
            ) {
                continue;
            }

            $existingListing = $existingListings->get($item['itemId']);
            $sellerDescription = $existingListing?->seller_description;
            $previousShortDescription = data_get($existingListing?->raw_payload, 'shortDescription');
            $needsSellerDescription = ! filled($sellerDescription) || $sellerDescription === $previousShortDescription;
            $summaryHasAdditionalImages = is_array($item['additionalImages'] ?? null);
            $detailsImagesFetched = data_get($existingListing?->raw_payload, 'detailsImagesFetched') === true;
            $summaryHasImages = is_string(data_get($item, 'image.imageUrl'))
                || collect($item['thumbnailImages'] ?? [])->isNotEmpty()
                || filled($existingListing?->image_url);
            $needsImageDetails = $summaryHasImages && ! $summaryHasAdditionalImages && ! $detailsImagesFetched;
            $browseShippingValue = data_get($item, 'shippingOptions.0.shippingCost.value');
            $needsShippingDetails = ! is_numeric($browseShippingValue);
            $itemDetails = null;
            if (
                $needsSellerDescription
                || $needsImageDetails
                || $needsShippingDetails
                || (
                    ! $this->hasAvailabilityInformation($item)
                    && $existingListing === null
                )
            ) {
                $itemDetails = $this->itemDetailsForImport($item['itemId']);
            }

            if ($itemDetails !== null && $this->isUnavailable($itemDetails)) {
                continue;
            }

            $additionalImages = collect($item['additionalImages'] ?? [])
                ->merge($itemDetails['additionalImages'] ?? [])
                ->filter(fn (mixed $image): bool => is_array($image) && is_string($image['imageUrl'] ?? null))
                ->unique('imageUrl')
                ->values()
                ->all();
            $item['additionalImages'] = $additionalImages;
            $item['detailsImagesFetched'] = $detailsImagesFetched || $itemDetails !== null || $summaryHasAdditionalImages;
            $primaryImageUrl = data_get($item, 'image.imageUrl')
                ?? data_get($itemDetails, 'image.imageUrl')
                ?? data_get($item['thumbnailImages'] ?? [], '0.imageUrl')
                ?? data_get($additionalImages, '0.imageUrl');

            $specifications = $this->listingSpecifications->extract($item['title'], $aspects);
            $shippingValue = $browseShippingValue
                ?? data_get($itemDetails, 'shippingOptions.0.shippingCost.value')
                ?? 0;
            $shipping = is_numeric($shippingValue) ? (float) $shippingValue : 0;
            $shippingType = data_get($item, 'shippingOptions.0.shippingCostType')
                ?? data_get($itemDetails, 'shippingOptions.0.shippingCostType')
                ?? null;
            $price = (float) data_get($item, 'price.value', 0);
            $totalPrice = $price + $shipping;

            if ($needsSellerDescription) {
                $sellerDescription = data_get($itemDetails, 'description');

                if (! is_string($sellerDescription) || trim($sellerDescription) === '') {
                    $sellerDescription = data_get($item, 'shortDescription');
                }
            }

            if (is_string($sellerDescription)) {
                $sellerDescription = $this->plainSellerDescription($sellerDescription);
            }

            $excludedMatch = $this->checkExcludedWords($item['title'], is_string($sellerDescription) ? $sellerDescription : '');

            $importedListing = DB::transaction(function () use ($item, $profile, $aspects, $specifications, $shipping, $shippingType, $price, $totalPrice, $sellerDescription, $user, $primaryImageUrl, $excludedMatch): array {
                // Check if this listing already exists for this user in any profile
                $existingListing = $user === null
                    ? null
                    : LaptopListing::withTrashed()
                        ->where('ebay_item_id', $item['itemId'])
                        ->whereHas('searchProfiles', fn (Builder $query) => $query->where('search_profiles.user_id', $user->id))
                        ->first();

                // Also check the old way (direct search_profile_id) for backward compatibility during migration
                if ($existingListing === null) {
                    $existingListing = LaptopListing::withTrashed()
                        ->where('search_profile_id', $profile->id)
                        ->where('ebay_item_id', $item['itemId'])
                        ->first();
                }

                // Fallback: check globally by ebay_item_id (in case pivot table is missing the link)
                if ($existingListing === null) {
                    $existingListing = LaptopListing::withTrashed()
                        ->where('ebay_item_id', $item['itemId'])
                        ->first();
                }

                // Skip updating deleted items — they should not reappear until restored
                if ($existingListing?->trashed() ?? false) {
                    return ['listing' => null, 'isNew' => false, 'priceDropped' => false, 'crossedTarget' => false, 'isNewAlert' => false, 'sharedPriceChanged' => false];
                }

                $isNewListing = $existingListing === null;
                $previousPrice = $existingListing?->total_price === null
                    ? null
                    : (float) $existingListing->total_price;
                $previousCurrency = $existingListing?->currency;
                $wasTrashed = false;
                $sellerDescription = is_string($sellerDescription) ? trim($sellerDescription) : '';

                $updateData = [
                    'ebay_item_id' => $item['itemId'],
                    'title' => $item['title'], 'item_url' => $item['itemWebUrl'], 'image_url' => $primaryImageUrl, 'condition' => $item['condition'] ?? null,
                    'price' => $price, 'currency' => data_get($item, 'price.currency', 'USD'), 'shipping_price' => $shipping, 'shipping_type' => $shippingType, 'total_price' => $totalPrice,
                    'seller' => data_get($item, 'seller.username'), 'seller_feedback' => data_get($item, 'seller.feedbackPercentage'),
                    ...($sellerDescription !== '' ? ['seller_description' => $sellerDescription] : []),
                    ...$specifications,
                    ...($aspects !== [] ? ['specifications' => $aspects] : []),
                    'raw_payload' => $item, 'seen_at' => now(),
                ];

                if ($isNewListing) {
                    $updateData['search_profile_id'] = $profile->id;
                }

                if ($excludedMatch !== null) {
                    $updateData['excluded'] = true;
                    $updateData['excluded_reason'] = $excludedMatch;
                } elseif ($existingListing?->excluded === true) {
                    $updateData['excluded'] = false;
                    $updateData['excluded_reason'] = null;
                }

                if ($isNewListing) {
                    $listing = LaptopListing::create($updateData);
                    // Link to profile via pivot table
                    $listing->searchProfiles()->syncWithoutDetaching([$profile->id => []]);
                } else {
                    $listing = $existingListing;
                    $listing->update($updateData);
                    // Ensure the profile is linked via pivot table (syncWithoutDetaching avoids duplicate key error)
                    $listing->searchProfiles()->syncWithoutDetaching([$profile->id => []]);
                }

                $currency = (string) $listing->currency;
                $priceChanged = $previousPrice === null
                    || (int) round($previousPrice * 100) !== (int) round($totalPrice * 100)
                    || $previousCurrency !== $currency;
                if ($priceChanged || ! $listing->priceHistory()->exists()) {
                    $listing->priceHistory()->create([
                        'price' => $totalPrice,
                        'currency' => $currency,
                        'observed_at' => now(),
                    ]);
                }

                $priceDropped = $previousPrice !== null
                    && $previousCurrency === $currency
                    && (int) round($totalPrice * 100) < (int) round($previousPrice * 100)
                    && ! $wasTrashed;
                $crossedTarget = false;
                $targetPrice = $profile->price_alert_threshold === null
                    ? null
                    : (float) $profile->price_alert_threshold;

                if (
                    $priceDropped
                    && $targetPrice !== null
                    && $previousPrice > $targetPrice
                    && $totalPrice <= $targetPrice
                    && $listing->price_threshold_notified_at === null
                ) {
                    $listing->forceFill(['price_threshold_notified_at' => now()])->save();
                    $crossedTarget = true;
                }

                // Find all profiles where this listing was found (via pivot table)
                $matchedProfiles = $user === null
                    ? collect()
                    : $listing->searchProfiles()
                        ->where('search_profiles.user_id', $user->id)
                        ->orderBy('name')
                        ->get(['search_profiles.id', 'name']);
                $profileNames = $matchedProfiles->pluck('name')->all();
                $hasSharedProfiles = count($profileNames) > 1;

                // With deduplication, there's only one listing per ebay_item_id, so shared listings check is simplified
                $sharedPricesAgree = ! $hasSharedProfiles || true;
                $alertCreated = false;
                $isNewAlert = false;
                $sharedPriceChanged = false;
                $previousObservedPrice = null;
                $previousObservedCurrency = null;
                if ($user !== null) {
                    $alertCreated = DB::table('user_listing_alerts')->insertOrIgnore([
                        'user_id' => $user->id,
                        'ebay_item_id' => $item['itemId'],
                        'created_at' => now(),
                        'last_observed_price' => $totalPrice,
                        'last_observed_currency' => $currency,
                    ]) === 1;
                    $userAlert = DB::table('user_listing_alerts')
                        ->where('user_id', $user->id)
                        ->where('ebay_item_id', $item['itemId'])
                        ->lockForUpdate()
                        ->first();
                    $previousObservedPrice = $userAlert->last_observed_price === null
                        ? null
                        : (float) $userAlert->last_observed_price;
                    $previousObservedCurrency = $userAlert->last_observed_currency;
                    $sharedPriceChanged = ! $alertCreated
                        && $previousObservedPrice !== null
                        && $sharedPricesAgree
                        && (
                            (int) round($previousObservedPrice * 100) !== (int) round($totalPrice * 100)
                            || $previousObservedCurrency !== $currency
                        );
                    if (! $hasSharedProfiles || $sharedPricesAgree) {
                        DB::table('user_listing_alerts')
                            ->where('user_id', $user->id)
                            ->where('ebay_item_id', $item['itemId'])
                            ->update([
                                'last_observed_price' => $totalPrice,
                                'last_observed_currency' => $currency,
                            ]);
                    }

                    if ($listing->wasRecentlyCreated && $alertCreated) {
                        $user->notify(new NewEbayListingNotification($listing, $profile));
                        $isNewAlert = true;
                    }

                    if (
                        $listing->wasRecentlyCreated
                        && ! $alertCreated
                        && $hasSharedProfiles
                        && $profile->notify_profile_matches
                    ) {
                        $user->notify(new SharedEbayListingNotification(
                            'profile_match',
                            $listing,
                            $profile,
                            $profileNames,
                        ));
                    }

                    if ($sharedPriceChanged && $profile->notify_shared_price_changes) {
                        $user->notify(new SharedEbayListingNotification(
                            'shared_price_change',
                            $listing,
                            $profile,
                            $profileNames,
                            $previousObservedPrice,
                            $previousObservedCurrency,
                        ));
                    }
                }

                return [
                    'listing' => $listing,
                    'is_new' => $isNewListing,
                    'is_new_alert' => $isNewAlert,
                    'is_profile_match' => $listing->wasRecentlyCreated
                        && ! $alertCreated
                        && $hasSharedProfiles
                        && $profile->notify_profile_matches,
                    'is_shared_listing' => $hasSharedProfiles,
                    'shared_price_changed' => $sharedPriceChanged && $profile->notify_shared_price_changes,
                    'previous_observed_price' => $previousObservedPrice,
                    'previous_observed_currency' => $previousObservedCurrency,
                    'price_dropped' => $priceDropped,
                    'previous_price' => $previousPrice,
                    'target_price' => $targetPrice,
                    'crossed_target' => $crossedTarget,
                ];
            });

            // Skip notifications and counters for deleted items
            if ($importedListing['listing'] === null) {
                continue;
            }

            $listing = $importedListing['listing'];

            // Only include newly created listings in the saved listings table
            if ($importedListing['is_new']) {
                $isExcluded = $listing->excluded;
                $searchParam = rawurlencode($listing->title);
                $savedListings[] = [
                    'title' => $listing->title,
                    'price' => (float) $listing->total_price,
                    'currency' => $listing->currency,
                    'item_url' => $listing->item_url,
                    'location' => $isExcluded ? 'Исключённый товар' : 'Список товаров',
                    'location_url' => ($isExcluded ? '/listings/excluded' : '/listings').'?search='.$searchParam.'&search_in=title',
                ];
            }

            if ($importedListing['is_new_alert']) {
                $newListingsForNotification[] = $importedListing['listing'];
            }

            if ($importedListing['is_profile_match']) {
                $profileMatchesForNotification[] = $importedListing['listing'];
            }

            if ($importedListing['shared_price_changed']) {
                $sharedPriceChangesForNotification[] = $importedListing;
            }

            $newListings += (int) $importedListing['is_new_alert'];

            if (
                $importedListing['price_dropped']
                && (! $importedListing['is_shared_listing'] || ! $profile->notify_shared_price_changes
                    || $importedListing['previous_observed_price'] === null)
            ) {
                $priceDropsForNotification[] = $importedListing;
            }

            $imported++;
        }

        if ($newListingsForNotification !== []) {
            $this->telegram->sendNewListings($user, $newListingsForNotification, $profile);
        }

        foreach ($profileMatchesForNotification as $listing) {
            $this->telegram->sendProfileMatch($user, $profile, $listing);
        }

        foreach ($sharedPriceChangesForNotification as $priceChange) {
            $this->telegram->sendSharedPriceChange(
                $user,
                $profile,
                $priceChange['listing'],
                $priceChange['previous_observed_price'],
                $priceChange['previous_observed_currency'],
            );
        }

        foreach ($priceDropsForNotification as $priceDrop) {
            $this->telegram->sendPriceDrop(
                $user,
                $profile,
                $priceDrop['listing'],
                $priceDrop['previous_price'],
                $priceDrop['target_price'],
                $priceDrop['crossed_target'],
            );
        }

        $processed = min($offset + count($items), $processable);
        $done = $processed >= $processable;
        $nextPartitions = [];
        $unprocessedCount = 0;
        if ($done && $found > $this->getMaxResults()) {
            [$nextPartitions, $unprocessedCount] = $this->pricePartitionsAfterLimit(
                $profile,
                $priceMinCents,
                $priceMaxCents,
                $processable,
                $items,
            );
        }

        return [
            'found' => $found,
            'processable' => $processable,
            'processed' => $processed,
            'page_processed' => count($items),
            'page_item_ids' => $ebayItemIds,
            'imported' => $imported,
            'new_listings' => $newListings,
            'next_offset' => $offset + count($items),
            'done' => $done,
            'next_partitions' => $nextPartitions,
            'unprocessed_count' => $unprocessedCount,
            'saved_listings' => $savedListings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchParameters(
        SearchProfile $profile,
        int $offset,
        ?int $priceMinCents = null,
        ?int $priceMaxCents = null,
    ): array {
        $parameters = [
            'q' => $profile->query,
            'limit' => 1,
            'offset' => $offset,
            'category_ids' => $this->getCategoryId(),
            'sort' => $this->getSortBy(),
            'filter' => $this->filters($profile, $priceMinCents, $priceMaxCents),
        ];
        $aspectFilters = $this->taxonomy->aspectFilterForProfile($profile);

        if ($aspectFilters !== null) {
            $parameters['aspect_filter'] = $aspectFilters;
        }

        return $parameters;
    }

    private function filters(
        SearchProfile $profile,
        ?int $priceMinCents = null,
        ?int $priceMaxCents = null,
    ): string {
        $minimumPriceCents = $profile->min_price === null
            ? null
            : (int) round((float) $profile->min_price * 100);
        $maximumPriceCents = $profile->max_price === null
            ? null
            : (int) round((float) $profile->max_price * 100);
        if ($priceMinCents !== null) {
            $minimumPriceCents = max($minimumPriceCents ?? 0, $priceMinCents);
        }
        if ($priceMaxCents !== null) {
            $maximumPriceCents = min($maximumPriceCents ?? $priceMaxCents, $priceMaxCents);
        }
        if (
            $minimumPriceCents !== null
            && $maximumPriceCents !== null
            && $minimumPriceCents > $maximumPriceCents
        ) {
            throw new RuntimeException('The eBay price partition does not overlap the profile price range.');
        }

        $hasPriceRange = $minimumPriceCents !== null || $maximumPriceCents !== null;
        $priceRange = $hasPriceRange
            ? 'price:['.$this->formatPriceFilterValue($minimumPriceCents ?? 0).'..'
                .($maximumPriceCents === null ? '' : $this->formatPriceFilterValue($maximumPriceCents)).']'
            : null;

        $filters = [
            $profile->condition ? 'conditions:{'.$profile->condition.'}' : null,
            $priceRange,
            $hasPriceRange ? 'priceCurrency:USD' : null,
        ];

        // Add free shipping filter if enabled
        $freeShipping = SiteSetting::query()->value('ebay_free_shipping_only');
        if ($freeShipping) {
            $filters[] = 'shipToLocations:FREE_SHIPPING';
        }

        // Add Buy It Now filter if enabled
        $buyItNow = SiteSetting::query()->value('ebay_buy_it_now_only');
        if ($buyItNow) {
            $filters[] = 'buyingOptions:{BuyItNow}';
        }

        // Add auction filter if enabled
        $auctionOnly = SiteSetting::query()->value('ebay_auction_only');
        if ($auctionOnly) {
            $filters[] = 'buyingOptions:{Auction}';
        }

        // Add seller country filter if set
        $sellerCountry = SiteSetting::query()->value('ebay_seller_country');
        if ($sellerCountry) {
            $filters[] = 'shipToLocations:'.$sellerCountry;
        }

        return collect($filters)->filter()->join(',');
    }

    private function formatPriceFilterValue(int $priceCents): string
    {
        return $priceCents % 100 === 0
            ? (string) intdiv($priceCents, 100)
            : number_format($priceCents / 100, 2, '.', '');
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{0: list<array{offset: int, price_min_cents: ?int, price_max_cents: ?int}>, 1: int}
     */
    private function pricePartitionsAfterLimit(
        SearchProfile $profile,
        ?int $priceMinCents,
        ?int $priceMaxCents,
        int $processable,
        array $items,
    ): array {
        $lastItem = $items === [] ? null : $items[array_key_last($items)];
        $boundaryPrice = is_array($lastItem) ? data_get($lastItem, 'price.value') : null;
        $boundaryCurrency = is_array($lastItem) ? data_get($lastItem, 'price.currency') : null;

        if (! is_numeric($boundaryPrice) || $boundaryCurrency !== 'USD') {
            throw new RuntimeException('eBay results cannot be continued because the last listing has no USD price.');
        }

        $boundaryPriceCents = (int) round((float) $boundaryPrice * 100);
        if ($boundaryPriceCents < 1) {
            throw new RuntimeException('eBay results cannot be continued because the boundary price is invalid.');
        }
        $effectiveMinCents = $priceMinCents;
        if ($profile->min_price !== null) {
            $profileMinCents = (int) round((float) $profile->min_price * 100);
            $effectiveMinCents = max($effectiveMinCents ?? $profileMinCents, $profileMinCents);
        }
        $effectiveMaxCents = $priceMaxCents;
        if ($profile->max_price !== null) {
            $profileMaxCents = (int) round((float) $profile->max_price * 100);
            $effectiveMaxCents = min($effectiveMaxCents ?? $profileMaxCents, $profileMaxCents);
        }

        $countBelowBoundary = 0;
        $belowMaxCents = $boundaryPriceCents - 1;
        if ($effectiveMinCents === null || $belowMaxCents >= $effectiveMinCents) {
            $countBelowBoundary = $this->searchResultCount(
                $this->searchParameters($profile, 0, $effectiveMinCents, $belowMaxCents),
            );
        }
        if ($countBelowBoundary > $processable) {
            Log::warning('eBay price ordering shifted during continuation; rescanning the result range in price partitions.', [
                'profile_id' => $profile->id,
                'boundary_price_cents' => $boundaryPriceCents,
                'count_below_boundary' => $countBelowBoundary,
                'processable' => $processable,
            ]);

            $partitions = [];
            if ($effectiveMinCents === null || $belowMaxCents >= $effectiveMinCents) {
                $partitions[] = [
                    'offset' => 0,
                    'price_min_cents' => $effectiveMinCents,
                    'price_max_cents' => $belowMaxCents,
                ];
            }

            $countAtBoundary = $this->searchResultCount(
                $this->searchParameters($profile, 0, $boundaryPriceCents, $boundaryPriceCents),
            );
            if ($countAtBoundary > 0) {
                $partitions[] = [
                    'offset' => 0,
                    'price_min_cents' => $boundaryPriceCents,
                    'price_max_cents' => $boundaryPriceCents,
                ];
            }

            $aboveMinCents = $boundaryPriceCents + 1;
            if ($effectiveMaxCents === null || $aboveMinCents <= $effectiveMaxCents) {
                $partitions[] = [
                    'offset' => 0,
                    'price_min_cents' => $aboveMinCents,
                    'price_max_cents' => $effectiveMaxCents,
                ];
            }

            return [$partitions, 0];
        }

        $countAtBoundary = $this->searchResultCount(
            $this->searchParameters($profile, 0, $boundaryPriceCents, $boundaryPriceCents),
        );
        $processedAtBoundary = max(0, $processable - $countBelowBoundary);
        $partitions = [];
        $unprocessedCount = 0;

        if ($countAtBoundary > $processedAtBoundary) {
            $maxResults = $this->getMaxResults();
            $pageSize = $this->getPageSize();
            if ($processedAtBoundary < $maxResults) {
                $partitions[] = [
                    'offset' => intdiv($processedAtBoundary, $pageSize) * $pageSize,
                    'price_min_cents' => $boundaryPriceCents,
                    'price_max_cents' => $boundaryPriceCents,
                ];
            } else {
                $unprocessedCount = $countAtBoundary - $processedAtBoundary;
            }
        }

        $aboveMinCents = $boundaryPriceCents + 1;
        if ($effectiveMaxCents === null || $aboveMinCents <= $effectiveMaxCents) {
            $partitions[] = [
                'offset' => 0,
                'price_min_cents' => $aboveMinCents,
                'price_max_cents' => $effectiveMaxCents,
            ];
        }

        return [$partitions, $unprocessedCount];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasAvailabilityInformation(array $item): bool
    {
        return $this->availabilityStatuses($item) !== [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isOutOfStock(array $item): bool
    {
        $statuses = $this->availabilityStatuses($item);

        return $statuses !== []
            && collect($statuses)->every(fn (string $status): bool => strtoupper($status) === 'OUT_OF_STOCK');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isUnavailable(array $item): bool
    {
        $itemEndDate = data_get($item, 'itemEndDate');

        return $this->isOutOfStock($item)
            || (is_string($itemEndDate) && $itemEndDate !== '' && Carbon::parse($itemEndDate)->isPast());
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function availabilityStatuses(array $item): array
    {
        $availabilities = data_get($item, 'estimatedAvailabilities');
        if (! is_array($availabilities)) {
            return [];
        }

        return collect($availabilities)
            ->pluck('estimatedAvailabilityStatus')
            ->filter(fn (mixed $status): bool => is_string($status) && trim($status) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function itemDetailsForImport(string $itemId): ?array
    {
        try {
            return $this->client->itemDetails($itemId);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Unable to fetch optional eBay item details; continuing with the search summary.', [
                'ebay_item_id' => $itemId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function plainSellerDescription(string $description): string
    {
        $description = preg_replace('/<\s*br\s*\/?>|<\/(?:p|div|li|tr|h[1-6])\s*>/i', "\n", $description) ?? $description;
        $description = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = preg_replace('/[ \t]+\n/u', "\n", $description) ?? $description;
        $description = preg_replace('/\n{3,}/u', "\n\n", $description) ?? $description;

        return trim($description);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  bool  $quickFail  When true, fails immediately on HTTP 429 without retrying (for synchronous AJAX requests)
     */
    private function fetchWithRetry(array $parameters, bool $quickFail = false): Response
    {
        $url = config('services.ebay.api_base_url').'/buy/browse/v1/item_summary/search';

        $settings = SiteSetting::query()->first();
        $enableRetry = $settings->ebay_enable_retry ?? true;
        $maxRetries = (int) ($settings->ebay_max_retries ?? 8);
        $baseDelay = (int) ($settings->ebay_base_delay_seconds ?? 30);

        // If retry is disabled or quick fail mode, skip retry logic
        if (! $enableRetry || $quickFail) {
            $maxRetries = 0;
        }

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $attemptLabel = ($attempt + 1).'/'.$maxRetries;
            Log::info('eBay API request attempt '.$attemptLabel, [
                'url' => $url,
                'query' => $parameters['q'] ?? null,
                'offset' => $parameters['offset'] ?? null,
            ]);

            try {
                $response = $this->client->client()->get($url, $parameters);
                // Track successful API call
                $this->incrementApiCallCount();
            } catch (ConnectionException $exception) {
                // Network/DNS errors are often transient — retry with backoff
                if ($attempt === $maxRetries) {
                    Log::error('eBay API connection failed after '.$maxRetries.' retries.', [
                        'attempt' => $attempt + 1,
                        'max_retries' => $maxRetries,
                        'error' => $exception->getMessage(),
                        'query' => $parameters['q'] ?? null,
                    ]);

                    throw $exception;
                }

                $retryAfter = (int) ($baseDelay * (2 ** $attempt));
                Log::warning('eBay API connection failed; retrying after '.$retryAfter.' seconds.', [
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries,
                    'retry_after' => $retryAfter,
                    'error' => $exception->getMessage(),
                    'query' => $parameters['q'] ?? null,
                ]);

                sleep($retryAfter);

                continue;
            } catch (RequestException $exception) {
                // HTTP 429 rate limit — retry with backoff
                $httpResponse = $exception->response;
                if ($httpResponse !== null && $httpResponse->status() === 429) {
                    if ($attempt === $maxRetries) {
                        Log::error('eBay API rate limit exceeded after '.$maxRetries.' retries.', [
                            'attempt' => $attempt + 1,
                            'max_retries' => $maxRetries,
                            'query' => $parameters['q'] ?? null,
                        ]);

                        throw $exception;
                    }

                    $headerRetryAfter = (int) ($httpResponse->header('Retry-After') ?? 0);
                    $retryAfter = $headerRetryAfter > 0 ? $headerRetryAfter : (int) ($baseDelay * (2 ** $attempt));
                    Log::warning('eBay API rate limit exceeded (HTTP 429); retrying after '.$retryAfter.' seconds.', [
                        'attempt' => $attempt + 1,
                        'max_retries' => $maxRetries,
                        'retry_after' => $retryAfter,
                        'query' => $parameters['q'] ?? null,
                    ]);

                    sleep($retryAfter);

                    continue;
                }

                // Non-429 HTTP errors should not be retried
                throw $exception;
            }

            if ($response->status() !== 429) {
                if ($attempt > 0) {
                    Log::info('eBay API request succeeded after '.$attempt.' retries (status: '.$response->status().').');
                }

                return $response->throw();
            }

            // On the last retry, throw immediately without waiting
            if ($attempt === $maxRetries) {
                Log::error('eBay API rate limit exceeded after '.$maxRetries.' retries.', [
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries,
                    'query' => $parameters['q'] ?? null,
                ]);

                return $response->throw();
            }

            // Use Retry-After header if provided and > 0, otherwise exponential backoff: 300s, 600s, 1200s...
            $headerRetryAfter = (int) ($response->header('Retry-After') ?? 0);
            $retryAfter = $headerRetryAfter > 0 ? $headerRetryAfter : (int) ($baseDelay * (2 ** $attempt));
            Log::warning('eBay API rate limit exceeded (HTTP 429); retrying after '.$retryAfter.' seconds.', [
                'attempt' => $attempt + 1,
                'max_retries' => $maxRetries,
                'retry_after' => $retryAfter,
                'query' => $parameters['q'] ?? null,
            ]);

            sleep($retryAfter);
        }

        return $response->throw();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  bool  $quickFail  When true, fails immediately on HTTP 429 without retrying
     */
    private function searchResultCount(array $parameters, bool $quickFail = false): int
    {
        $response = $this->fetchWithRetry($parameters, $quickFail);
        $total = $response->json('total');

        if (! is_int($total) || $total < 0) {
            throw new RuntimeException('eBay returned an invalid result count while checking profile filters.');
        }

        return $total;
    }

    /**
     * Increment the daily API call counter in the database.
     */
    private function incrementApiCallCount(): void
    {
        $settings = SiteSetting::query()->first();
        $today = now()->format('Y-m-d');

        // Reset counter if it's a new day
        if ($settings->ebay_api_daily_count_date?->format('Y-m-d') !== $today) {
            $settings->ebay_api_daily_count_date = now();
            $settings->ebay_api_daily_count = 1;
        } else {
            $settings->ebay_api_daily_count++;
        }

        $settings->save();
    }

    /**
     * Get the daily API usage statistics.
     *
     * @return array{used: int, limit: int, remaining: int, percentage: int}
     */
    public function getDailyApiUsage(): array
    {
        $limit = 5000;
        $settings = SiteSetting::query()->first();
        $today = now()->format('Y-m-d');

        // Return 0 if it's a new day and counter hasn't been set yet
        $used = $settings->ebay_api_daily_count_date?->format('Y-m-d') === $today
            ? (int) $settings->ebay_api_daily_count
            : 0;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'percentage' => (int) floor($used / $limit * 100),
        ];
    }

    /**
     * Check if the daily API call count has reached the configured threshold.
     *
     * When true, parsing should stop to avoid hitting the hard daily limit.
     */
    private function hasReachedApiLimit(): bool
    {
        $thresholdPercent = (int) (SiteSetting::query()->value('ebay_api_limit_threshold_percent') ?? 90);
        $limit = 5000;
        $threshold = (int) floor($limit * $thresholdPercent / 100);

        $settings = SiteSetting::query()->first();
        $today = now()->format('Y-m-d');
        $used = $settings->ebay_api_daily_count_date?->format('Y-m-d') === $today
            ? (int) $settings->ebay_api_daily_count
            : 0;

        if ($used >= $threshold) {
            Log::warning("eBay API daily limit threshold reached: {$used}/{$limit} ({$thresholdPercent}% threshold = {$threshold} calls).", [
                'used' => $used,
                'limit' => $limit,
                'threshold_percent' => $thresholdPercent,
                'threshold' => $threshold,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Like searchResultCount but adds a delay between requests to avoid rate limiting
     * when checking multiple filters sequentially.
     *
     * @param  array<string, mixed>  $parameters
     * @param  bool  $quickFail  When true, fails immediately on HTTP 429 without retrying
     */
    private function searchResultCountWithDelay(array $parameters, bool $quickFail = false): int
    {
        // Add delay between API calls to avoid rate limiting (not before the first call)
        if ($this->filterCheckCallCount > 0) {
            $delay = SiteSetting::query()->value('ebay_filter_check_delay_seconds') ?? config('services.ebay.filter_check_delay_seconds', 3);
            Log::info("Filter check rate limit delay: {$delay}s (call #{$this->filterCheckCallCount}).");
            sleep((int) $delay);
        }
        $this->filterCheckCallCount++;

        return $this->searchResultCount($parameters, $quickFail);
    }

    /**
     * @param  list<array{label: string, value: string, result_count: int, reduction: int, remaining_percent: ?float}>  $filterImpacts
     */
    private function filterRecommendation(int $baselineCount, int $filteredCount, array $filterImpacts): ?string
    {
        if ($baselineCount === 0) {
            return 'Даже без дополнительных фильтров eBay не находит товары. Проверьте поисковый запрос.';
        }

        if ($filteredCount === 0) {
            $mostRestrictiveFilter = $filterImpacts[0]['label'] ?? null;

            return $mostRestrictiveFilter === null
                ? 'Проверьте поисковый запрос: с текущими настройками товары не найдены.'
                : "С текущими настройками товары не найдены. Попробуйте ослабить фильтр «{$mostRestrictiveFilter}» и проверить поиск снова.";
        }

        if ($filteredCount <= 10 || $filteredCount / $baselineCount <= 0.1) {
            $mostRestrictiveFilter = $filterImpacts[0]['label'] ?? null;

            return $mostRestrictiveFilter === null
                ? 'Выдача очень мала. Проверьте поисковый запрос.'
                : "Фильтры сильно ограничивают выдачу. Проверьте, можно ли ослабить «{$mostRestrictiveFilter}».";
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function recordActivity(
        SearchProfile $profile,
        string $action,
        string $description,
        ?array $metadata,
        string $runId,
        ?int $pid = null,
    ): void {
        SearchProfileActivity::query()->create([
            'search_profile_id' => $profile->id,
            'user_id' => $profile->user_id ?? auth('admin')->id() ?? auth()->id(),
            'profile_owner_id' => $profile->user_id,
            'profile_name' => $profile->name,
            'action' => $action,
            'description' => $description,
            'run_id' => $runId,
            'pid' => $pid,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if the listing title or seller description contains any excluded words.
     *
     * Returns the matched word if found, null otherwise.
     * Some common phrases are excluded from matching (e.g., "power button", "caps lock").
     */
    private function checkExcludedWords(string $title, string $sellerDescription): ?string
    {
        $words = ExcludedWord::query()->where('active', true)->pluck('word')->all();
        if ($words === []) {
            return null;
        }

        foreach ($words as $word) {
            $pattern = '/\b'.preg_quote($word, '/').'\b/iu';

            if (preg_match($pattern, $title)) {
                if ($this->isExcludedPhraseMatch($title, $word)) {
                    continue;
                }

                return $word;
            }

            if (preg_match($pattern, $sellerDescription)) {
                if ($this->isExcludedPhraseMatch($sellerDescription, $word)) {
                    continue;
                }

                return $word;
            }
        }

        return null;
    }

    /**
     * Check if the matched word is part of an excluded phrase that should not trigger exclusion.
     * For example, "power" in "power button" should not match the "power" excluded word.
     */
    private function isExcludedPhraseMatch(string $text, string $word): bool
    {
        $lowerWord = mb_strtolower($word);

        // Define phrases that should NOT trigger exclusion for specific words
        $excludedPhrases = [
            'power' => ['power button', 'power supply', 'power adapter', 'power cord', 'power jack', 'power cable'],
            'lock' => ['caps lock', 'num lock', 'scroll lock', 'shift lock'],
            'no power' => ['no power adapter', 'no power cord', 'no power cable'],
        ];

        if (! isset($excludedPhrases[$lowerWord])) {
            return false;
        }

        $lowerText = mb_strtolower($text);

        foreach ($excludedPhrases[$lowerWord] as $phrase) {
            if (mb_strpos($lowerText, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }
}
