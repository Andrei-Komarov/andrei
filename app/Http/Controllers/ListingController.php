<?php

namespace App\Http\Controllers;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SiteSetting;
use App\Models\UserListingNote;
use App\Services\ListingDuplicateProfileService;
use App\Services\ListingSpecificationService;
use App\Services\ListingValueAssessmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public function index(
        Request $request,
        ListingValueAssessmentService $valueAssessment,
        ListingDuplicateProfileService $duplicateProfiles,
        ListingSpecificationService $listingSpecifications,
    ): View {
        return $this->listingPage($request, $valueAssessment, $duplicateProfiles, $listingSpecifications);
    }

    private function listingPage(
        Request $request,
        ListingValueAssessmentService $valueAssessment,
        ListingDuplicateProfileService $duplicateProfiles,
        ListingSpecificationService $listingSpecifications,
        ?string $fixedStatus = null,
    ): View {
        $conditions = LaptopListing::query()
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->whereNotNull('condition')
            ->distinct()
            ->orderBy('condition')
            ->pluck('condition');

        $priceMinRules = ['nullable', 'numeric', 'min:0'];
        $priceMaxRules = ['nullable', 'numeric', 'min:0'];
        $dateFromRules = ['nullable', 'date'];
        $dateToRules = ['nullable', 'date'];

        if ($request->filled('price_max')) {
            $priceMinRules[] = 'lte:price_max';
        }

        if ($request->filled('price_min')) {
            $priceMaxRules[] = 'gte:price_min';
        }

        if ($request->filled('date_to')) {
            $dateFromRules[] = 'before_or_equal:date_to';
        }

        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $brands = ListingSpecificationService::LAPTOP_BRANDS;

        $processors = LaptopListing::query()
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->where('excluded', false)
            ->whereNotNull('cpu')
            ->selectRaw('DISTINCT ON (LOWER(cpu)) cpu')
            ->orderByRaw('LOWER(cpu), cpu')
            ->pluck('cpu');

        $rams = LaptopListing::query()
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->where('excluded', false)
            ->whereNotNull('ram_gb')
            ->distinct()
            ->orderBy('ram_gb')
            ->pluck('ram_gb');

        $validated = $request->validate([
            'profile_id' => [
                'nullable',
                'integer',
                Rule::exists('search_profiles', 'id')->where('user_id', $request->user()->id),
            ],
            'status' => ['nullable', Rule::in(['new', 'watching', 'buy', 'skipped'])],
            'price_min' => $priceMinRules,
            'price_max' => $priceMaxRules,
            'condition' => ['nullable', Rule::in($conditions->all())],
            'brand' => ['nullable', Rule::in($brands)],
            'processor' => ['nullable', Rule::in($processors->all())],
            'ram' => ['nullable', 'integer', Rule::in($rams->all())],
            'date_from' => $dateFromRules,
            'date_to' => $dateToRules,
            'sort' => ['nullable', Rule::in([
                'title_asc',
                'title_desc',
                'price_asc',
                'price_desc',
                'status_asc',
                'status_desc',
                'condition_asc',
                'condition_desc',
                'date_asc',
                'date_desc',
                'brand_asc',
                'brand_desc',
                'specifications_asc',
                'specifications_desc',
                'seller_asc',
                'seller_desc',
                'seller_description_asc',
                'seller_description_desc',
                'note_asc',
                'note_desc',
                'value_asc',
                'value_desc',
            ])],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100, 200])],
            'search' => ['nullable', 'string', 'max:255'],
            'search_in' => ['nullable', Rule::in(['title', 'seller_description'])],
        ]);
        if ($fixedStatus !== null) {
            $validated['status'] = $fixedStatus;
        }

        $selectedProfileId = $validated['profile_id'] ?? null;

        // Load search settings from database
        $searchSettings = SiteSetting::query()->first();
        $defaultSort = $searchSettings->search_default_sort ?? 'price_asc';
        $defaultSearchField = $searchSettings->search_default_field ?? 'title';
        $defaultStatus = $searchSettings->search_default_status ?? null;
        $defaultPriceMin = $searchSettings->search_price_min ?? 0;
        $defaultPriceMax = $searchSettings->search_price_max ?? 0;
        $defaultCondition = $searchSettings->search_default_condition ?? null;

        $sort = $validated['sort'] ?? $defaultSort;
        $defaultPerPage = $searchSettings->appearance_default_per_page ?? 25;
        $perPage = (int) ($validated['per_page'] ?? $defaultPerPage);

        // Apply global filters from settings
        if ($defaultPriceMin > 0 && ! isset($validated['price_min'])) {
            $validated['price_min'] = $defaultPriceMin;
        }
        if ($defaultPriceMax > 0 && ! isset($validated['price_max'])) {
            $validated['price_max'] = $defaultPriceMax;
        }
        if ($defaultStatus && ! isset($validated['status'])) {
            $validated['status'] = $defaultStatus;
        }
        if ($defaultCondition && ! isset($validated['condition'])) {
            $validated['condition'] = $defaultCondition;
        }

        // Use default search field if not specified
        if (! isset($validated['search_in'])) {
            $validated['search_in'] = $defaultSearchField;
        }
        $queryGrammar = DB::connection()->getQueryGrammar();
        $brandSortExpression = 'LOWER(COALESCE('
            .$queryGrammar->wrap('specifications->brand').', '
            .$queryGrammar->wrap('specifications->Brand').', '
            .$queryGrammar->wrap('title').'))';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $sortColumn = match ($sortKey) {
            'title' => 'title',
            'price' => 'total_price',
            'status' => 'status',
            'condition' => 'condition',
            'date' => 'created_at',
            'seller' => 'seller',
            'seller_description' => 'seller_description',
            default => null,
        };
        $profiles = SearchProfile::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['profileListings' => fn ($query) => $query->whereNull('laptop_listings.deleted_at')])
            ->orderBy('name')
            ->get();
        $selectedProfile = $selectedProfileId === null
            ? null
            : $profiles->firstWhere('id', (int) $selectedProfileId);
        // With deduplication, listings are unique per ebay_item_id.
        // Filter by user's profiles via the pivot table.
        $profileIds = SearchProfile::query()
            ->where('user_id', $request->user()->id)
            ->pluck('id')
            ->all();

        $listingsQuery = LaptopListing::query()
            ->with('searchProfiles')
            ->whereHas('searchProfiles', fn ($query) => $query->whereIn('search_profiles.id', $profileIds))
            ->where('excluded', false)
            ->when($fixedStatus === null, fn ($query) => $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'new');
            }))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($selectedProfileId, fn ($query, $profileId) => $query->whereHas('searchProfiles', fn ($q) => $q->where('search_profiles.id', $profileId)))
            ->when(isset($validated['price_min']), fn ($query) => $query->where('total_price', '>=', $validated['price_min']))
            ->when(isset($validated['price_max']), fn ($query) => $query->where('total_price', '<=', $validated['price_max']))
            ->when($validated['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['search'] ?? null, function ($query, string $search) use ($validated): void {
                $searchColumn = ($validated['search_in'] ?? 'title') === 'seller_description'
                    ? 'seller_description'
                    : 'title';
                $searchWords = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

                $query->where(function ($query) use ($searchColumn, $searchWords): void {
                    foreach ($searchWords as $word) {
                        $query->whereLike($searchColumn, '%'.$word.'%');
                    }
                });
            })
            ->when($validated['brand'] ?? null, function ($query, string $brand): void {
                $query->where(function ($query) use ($brand): void {
                    $query->whereRaw("LOWER((specifications->>'brand')) = LOWER(?)", [$brand])
                        ->orWhereRaw("LOWER((specifications->>'Brand')) = LOWER(?)", [$brand])
                        ->orWhereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($brand).'%']);
                });
            })
            ->when($validated['processor'] ?? null, fn ($query, string $processor) => $query->whereRaw('LOWER(cpu) = LOWER(?)', [$processor]))
            ->when($validated['ram'] ?? null, fn ($query, int $ram) => $query->where('ram_gb', $ram));
        if ($sortKey === 'brand') {
            $listingsQuery->orderByRaw("{$brandSortExpression} {$sortDirection}");
        } elseif ($sortKey === 'specifications') {
            foreach (['cpu', 'ram_gb', 'storage', 'screen', 'gpu'] as $specificationColumn) {
                $listingsQuery->orderBy($specificationColumn, $sortDirection);
            }
        } elseif ($sortKey === 'note') {
            $listingsQuery->orderBy(
                UserListingNote::query()
                    ->select('note')
                    ->where('user_id', $request->user()->id)
                    ->whereColumn('ebay_item_id', 'laptop_listings.ebay_item_id')
                    ->limit(1),
                $sortDirection,
            );
        } elseif ($sortKey !== 'value') {
            $listingsQuery->orderBy($sortColumn ?? 'total_price', $sortDirection);
        }
        $allValueAssessments = null;
        if ($sortKey === 'value') {
            $allListings = $listingsQuery->orderByDesc('id')->get();
            $allValueAssessments = $valueAssessment->assessPage($allListings);
            $orderedListings = $allListings
                ->sortBy(
                    fn (LaptopListing $listing): int => $allValueAssessments[$listing->id]['score']
                        ?? ($sortDirection === 'asc' ? PHP_INT_MAX : PHP_INT_MIN),
                    SORT_NUMERIC,
                    $sortDirection === 'desc',
                )
                ->values();
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $listings = new LengthAwarePaginator(
                $orderedListings->forPage($currentPage, $perPage),
                $orderedListings->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        } else {
            $listings = $listingsQuery
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        }
        $collection = $listings->getCollection();
        $modelKeys = $collection->pluck('id')->all();
        $valueAssessments = $allValueAssessments === null
            ? $valueAssessment->assessPage($collection)
            : collect($allValueAssessments)->only($modelKeys)->all();
        $listingGroupsById = $duplicateProfiles->listingGroupsForListings(
            $request->user(),
            $modelKeys,
        );
        $listingNotesByEbayItemId = UserListingNote::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('ebay_item_id', $listingGroupsById->flatten()->pluck('ebay_item_id')->unique())
            ->pluck('note', 'ebay_item_id');
        $displaySpecifications = $listings->getCollection()->mapWithKeys(
            fn (LaptopListing $listing): array => [$listing->id => [
                ...$listingSpecifications->forListing($listing),
                'brand' => $listingSpecifications->brandForListing($listing),
            ]],
        );

        return view('listings.index', [
            'listingSectionTitle' => match ($fixedStatus) {
                'watching' => 'Список наблюдения',
                'buy' => 'Список покупок',
                default => 'Список товаров',
            },
            'listingSectionDescription' => match ($fixedStatus) {
                'watching' => 'Товары, отмеченные статусом «Наблюдаю»',
                'buy' => 'Товары, отмеченные для покупки',
                default => 'Все товары, найденные вашими профилями поиска',
            },
            'listings' => $listings,
            'valueAssessments' => $valueAssessments,
            'listingGroupsById' => $listingGroupsById,
            'listingNotesByEbayItemId' => $listingNotesByEbayItemId,
            'displaySpecifications' => $displaySpecifications,
            'profiles' => $profiles,
            'conditions' => $conditions,
            'brands' => $brands,
            'processors' => $processors,
            'rams' => $rams,
            'selectedProfile' => $selectedProfile,
            'filters' => $validated,
            'sort' => $sort,
            'perPage' => $perPage,
            'showPerPageSelector' => $fixedStatus === null,
        ]);
    }

    public function watchlist(
        Request $request,
        ListingValueAssessmentService $valueAssessment,
        ListingDuplicateProfileService $duplicateProfiles,
        ListingSpecificationService $listingSpecifications,
    ): View {
        $request->merge(['status' => 'watching']);

        return $this->listingPage($request, $valueAssessment, $duplicateProfiles, $listingSpecifications, 'watching');
    }

    public function buyList(
        Request $request,
        ListingValueAssessmentService $valueAssessment,
        ListingDuplicateProfileService $duplicateProfiles,
        ListingSpecificationService $listingSpecifications,
    ): View {
        $request->merge(['status' => 'buy']);

        return $this->listingPage($request, $valueAssessment, $duplicateProfiles, $listingSpecifications, 'buy');
    }

    public function show(Request $request, LaptopListing $listing): View
    {
        abort_unless($listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists(), 404);

        return view('listings.show', [
            'listing' => $listing,
        ]);
    }

    public function priceHistory(Request $request, LaptopListing $listing): View
    {
        abort_unless($listing->searchProfiles()->where('search_profiles.user_id', $request->user()->id)->exists(), 404);

        $sort = $request->validate([
            'sort' => ['nullable', Rule::in(['date_asc', 'date_desc', 'price_asc', 'price_desc'])],
        ])['sort'] ?? 'date_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $history = $listing->priceHistory()
            ->orderBy($sortKey === 'price' ? 'price' : 'observed_at', $sortDirection)
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('listings.price-history', [
            'listing' => $listing,
            'history' => $history,
            'sort' => $sort,
        ]);
    }

    public function excluded(Request $request, ListingDuplicateProfileService $duplicateProfiles): View
    {
        $conditions = LaptopListing::query()
            ->where('excluded', true)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->whereNotNull('condition')
            ->distinct()
            ->orderBy('condition')
            ->pluck('condition');

        $priceMinRules = ['nullable', 'numeric', 'min:0'];
        $priceMaxRules = ['nullable', 'numeric', 'min:0'];
        $dateFromRules = ['nullable', 'date'];
        $dateToRules = ['nullable', 'date'];

        if ($request->filled('price_max')) {
            $priceMinRules[] = 'lte:price_max';
        }

        if ($request->filled('price_min')) {
            $priceMaxRules[] = 'gte:price_min';
        }

        if ($request->filled('date_to')) {
            $dateFromRules[] = 'before_or_equal:date_to';
        }

        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $excludedBrands = ListingSpecificationService::LAPTOP_BRANDS;

        $excludedProcessors = LaptopListing::query()
            ->where('excluded', true)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->whereNotNull('cpu')
            ->selectRaw('DISTINCT ON (LOWER(cpu)) cpu')
            ->orderByRaw('LOWER(cpu), cpu')
            ->pluck('cpu');

        $excludedRams = LaptopListing::query()
            ->where('excluded', true)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->whereNotNull('ram_gb')
            ->distinct()
            ->orderBy('ram_gb')
            ->pluck('ram_gb');

        $validated = $request->validate([
            'profile_id' => [
                'nullable',
                'integer',
                Rule::exists('search_profiles', 'id')->where('user_id', $request->user()->id),
            ],
            'status' => ['nullable', Rule::in(['new', 'watching', 'buy', 'skipped'])],
            'price_min' => $priceMinRules,
            'price_max' => $priceMaxRules,
            'condition' => ['nullable', Rule::in($conditions->all())],
            'brand' => ['nullable', Rule::in($excludedBrands)],
            'processor' => ['nullable', Rule::in($excludedProcessors->all())],
            'ram' => ['nullable', 'integer', Rule::in($excludedRams->all())],
            'date_from' => $dateFromRules,
            'date_to' => $dateToRules,
            'sort' => ['nullable', Rule::in([
                'title_asc', 'title_desc',
                'price_asc', 'price_desc',
                'condition_asc', 'condition_desc',
                'date_asc', 'date_desc',
                'seller_asc', 'seller_desc',
                'seller_description_asc', 'seller_description_desc',
            ])],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100, 200])],
            'search' => ['nullable', 'string', 'max:255'],
            'search_in' => ['nullable', Rule::in(['title', 'seller_description'])],
        ]);

        $selectedProfileId = $validated['profile_id'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 25);
        $sort = $validated['sort'] ?? 'date_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));

        $profiles = SearchProfile::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['profileListings' => fn ($query) => $query->where('laptop_listings.excluded', true)])
            ->orderBy('name')
            ->get();

        $selectedProfile = $selectedProfileId === null
            ? null
            : $profiles->firstWhere('id', (int) $selectedProfileId);

        // With deduplication, each listing is unique. Just filter by user's profiles via pivot.
        $listings = LaptopListing::query()
            ->where('excluded', true)
            ->whereHas('searchProfiles', fn ($q) => $q->where('search_profiles.user_id', $request->user()->id))
            ->with('searchProfiles')
            ->when($selectedProfileId, fn ($query, $profileId) => $query->whereHas('searchProfiles', fn ($q) => $q->where('search_profiles.id', $profileId)))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(isset($validated['price_min']), fn ($query) => $query->where('total_price', '>=', $validated['price_min']))
            ->when(isset($validated['price_max']), fn ($query) => $query->where('total_price', '<=', $validated['price_max']))
            ->when($validated['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['search'] ?? null, function ($query, string $search) use ($validated): void {
                $searchColumn = ($validated['search_in'] ?? 'title') === 'seller_description'
                    ? 'seller_description'
                    : 'title';
                $searchWords = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

                $query->where(function ($query) use ($searchColumn, $searchWords): void {
                    foreach ($searchWords as $word) {
                        $query->whereLike($searchColumn, '%'.$word.'%');
                    }
                });
            })
            ->when($validated['brand'] ?? null, function ($query, string $brand): void {
                $query->where(function ($query) use ($brand): void {
                    $query->whereRaw("LOWER((specifications->>'brand')) = LOWER(?)", [$brand])
                        ->orWhereRaw("LOWER((specifications->>'Brand')) = LOWER(?)", [$brand])
                        ->orWhereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($brand).'%']);
                });
            })
            ->when($validated['processor'] ?? null, fn ($query, string $processor) => $query->whereRaw('LOWER(cpu) = LOWER(?)', [$processor]))
            ->when($validated['ram'] ?? null, fn ($query, int $ram) => $query->where('ram_gb', $ram))
            ->orderBy(match ($sortKey) {
                'title' => 'title',
                'price' => 'total_price',
                'condition' => 'condition',
                'seller' => 'seller',
                'seller_description' => 'seller_description',
                default => 'created_at',
            }, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $listingGroupsById = $duplicateProfiles->listingGroupsForListings(
            $request->user(),
            $listings->getCollection()->pluck('id')->all(),
        );

        return view('listings.excluded', [
            'listings' => $listings,
            'profiles' => $profiles,
            'conditions' => $conditions,
            'brands' => $excludedBrands,
            'processors' => $excludedProcessors,
            'rams' => $excludedRams,
            'selectedProfile' => $selectedProfile,
            'filters' => $validated,
            'sort' => $sort,
            'perPage' => $perPage,
            'showPerPageSelector' => $listings->total() > 25,
            'listingGroupsById' => $listingGroupsById,
        ]);
    }

    public function deleted(Request $request): View
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100, 200])],
            'sort' => ['nullable', Rule::in([
                'title_asc', 'title_desc',
                'price_asc', 'price_desc',
                'deleted_asc', 'deleted_desc',
            ])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 25);
        $sort = $validated['sort'] ?? 'deleted_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $listings = LaptopListing::onlyTrashed()
            ->with('searchProfiles')
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->orderBy(match ($sortKey) {
                'title' => 'title',
                'price' => 'total_price',
                default => 'deleted_at',
            }, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('listings.deleted', [
            'listings' => $listings,
            'sort' => $sort,
            'perPage' => $perPage,
            'showPerPageSelector' => $listings->total() > 25,
        ]);
    }

    public function priceChanges(Request $request): View
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100, 200])],
            'sort' => ['nullable', Rule::in([
                'title_asc', 'title_desc',
                'price_asc', 'price_desc',
                'change_asc', 'change_desc',
                'date_asc', 'date_desc',
            ])],
            'status' => ['nullable', Rule::in(['active', 'excluded', 'all'])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 25);
        $sort = $validated['sort'] ?? 'date_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $status = $validated['status'] ?? 'active';

        // Get listings that have price changes (at least 2 price history records with different prices)
        $listingIdsWithChanges = DB::table('laptop_listing_price_histories')
            ->select('laptop_listing_id')
            ->whereColumn('price', '<>', DB::raw('(SELECT MIN(price) FROM laptop_listing_price_histories AS h2 WHERE h2.laptop_listing_id = laptop_listing_price_histories.laptop_listing_id)'))
            ->distinct()
            ->pluck('laptop_listing_id');

        $listings = LaptopListing::query()
            ->with('searchProfiles')
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $request->user()->id))
            ->when($status === 'active', fn ($q) => $q->where('excluded', false))
            ->when($status === 'excluded', fn ($q) => $q->where('excluded', true))
            ->whereIn('id', $listingIdsWithChanges)
            ->with(['priceHistory' => fn ($q) => $q->orderByDesc('observed_at')->orderByDesc('id')->limit(2)])
            ->orderBy(match ($sortKey) {
                'title' => 'title',
                'price' => 'total_price',
                'change' => 'updated_at',
                default => 'updated_at',
            }, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('listings.price-changes', [
            'listings' => $listings,
            'sort' => $sort,
            'perPage' => $perPage,
            'status' => $status,
            'showPerPageSelector' => $listings->total() > 25,
        ]);
    }
}
