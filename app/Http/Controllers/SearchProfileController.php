<?php

namespace App\Http\Controllers;

use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Services\EbayBrowseService;
use App\Services\EbayTaxonomyService;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class SearchProfileController extends Controller
{
    private const ANY_FILTER_VALUE = '__any__';

    public function __construct(private EbayTaxonomyService $taxonomy) {}

    public function index(Request $request): View
    {
        $sort = $request->validate([
            'sort' => ['nullable', Rule::in([
                'name_asc', 'name_desc',
                'query_asc', 'query_desc',
                'criteria_asc', 'criteria_desc',
                'listings_asc', 'listings_desc',
                'min_price_asc', 'min_price_desc',
                'max_price_asc', 'max_price_desc',
                'status_asc', 'status_desc',
                'schedule_asc', 'schedule_desc',
                'last_search_asc', 'last_search_desc',
            ])],
        ])['sort'] ?? 'name_asc';
        $profiles = SearchProfile::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['profileListings as listings_count' => fn ($query) => $query->whereNull('laptop_listings.deleted_at')])
            ->orderBy('name')
            ->get();
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $profiles = $profiles->sortBy(
            fn (SearchProfile $profile): mixed => match ($sortKey) {
                'query' => $profile->query,
                'criteria' => implode(' ', [
                    $profile->condition,
                    $profile->min_price,
                    $profile->max_price,
                    collect($profile->brands ?: [$profile->brand])->filter()->join(' '),
                    collect($profile->processors ?: [$profile->processor])->filter()->join(' '),
                    collect($profile->ram_sizes ?: [$profile->ram_size ?? $profile->min_ram_gb])->filter()->join(' '),
                    collect($profile->storage_capacities ?: [$profile->storage_capacity])->filter()->join(' '),
                    collect($profile->screen_sizes ?: [$profile->screen_size])->filter()->join(' '),
                    collect($profile->gpus ?: [$profile->gpu])->filter()->join(' '),
                    $profile->operating_system,
                ]),
                'listings' => $profile->listings_count,
                'min_price' => $profile->min_price ?? -1,
                'max_price' => $profile->max_price ?? PHP_INT_MAX,
                'status' => (int) $profile->active,
                'schedule' => $profile->schedule_interval_hours.' '.$profile->schedule_time,
                'last_search' => $profile->last_synced_at?->timestamp ?? 0,
                default => $profile->name,
            },
            SORT_NATURAL | SORT_FLAG_CASE,
            $sortDirection === 'desc',
        )->values();

        $ebayBrowseService = app(EbayBrowseService::class);
        $apiUsage = $ebayBrowseService->getDailyApiUsage();

        return view('profiles.index', ['profiles' => $profiles, 'sort' => $sort, 'apiUsage' => $apiUsage]);
    }

    public function history(Request $request): View
    {
        $sort = $request->validate([
            'sort' => ['nullable', Rule::in([
                'date_asc', 'date_desc',
                'event_asc', 'event_desc',
                'profile_asc', 'profile_desc',
                'user_asc', 'user_desc',
                'details_asc', 'details_desc',
            ])],
        ])['sort'] ?? 'date_desc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $sortColumn = match ($sortKey) {
            'event' => 'search_profile_activities.action',
            'profile' => 'search_profile_activities.profile_name',
            'user' => 'users.name',
            'details' => 'search_profile_activities.description',
            default => 'search_profile_activities.created_at',
        };
        $activities = SearchProfileActivity::query()
            ->where('profile_owner_id', $request->user()->id)
            ->leftJoin('users', 'users.id', '=', 'search_profile_activities.user_id')
            ->select('search_profile_activities.*')
            ->with(['user', 'searchProfile'])
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('search_profile_activities.id')
            ->paginate(50)
            ->withQueryString();

        return view('profiles.history', ['activities' => $activities, 'sort' => $sort]);
    }

    public function create(): View
    {
        return view('profiles.create');
    }

    public function edit(SearchProfile $profile): View
    {
        $this->ensureProfileOwner($profile);

        return view('profiles.edit', ['profile' => $profile]);
    }

    public function filterOptions(): JsonResponse
    {
        if (! config('services.ebay.client_id') || ! config('services.ebay.client_secret')) {
            return response()->json([
                'message' => 'Настройте EBAY_CLIENT_ID и EBAY_CLIENT_SECRET для загрузки фильтров eBay.',
            ], 503);
        }

        try {
            return response()->json(['filters' => $this->taxonomy->laptopAspectOptions()]);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Не удалось загрузить варианты фильтров eBay. Проверьте подключение и настройки API.',
            ], 502);
        }
    }

    public function analyzeFilters(Request $request, SearchProfile $profile, EbayBrowseService $ebay): JsonResponse
    {
        $this->ensureProfileOwner($profile);

        if (! config('services.ebay.client_id') || ! config('services.ebay.client_secret')) {
            return response()->json([
                'message' => 'Настройте EBAY_CLIENT_ID и EBAY_CLIENT_SECRET для анализа фильтров.',
            ], 503);
        }

        try {
            return response()->json($ebay->analyzeFilterImpact($profile));
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Не удалось проверить фильтры eBay. Попробуйте ещё раз позже.',
            ], 502);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProfileData($request);
        $data['schedule_time'] ??= '08:00';
        $data['schedule_interval_hours'] ??= 24;
        $profile = SearchProfile::create([...$data, 'user_id' => $request->user()->id]);
        $this->recordActivity(
            $profile,
            'created',
            "Создан профиль поиска «{$profile->name}» с запросом «{$profile->query}».",
        );

        return to_route('profiles.index')->with('success', 'Профиль поиска создан.');
    }

    public function update(Request $request, SearchProfile $profile): RedirectResponse
    {
        $this->ensureProfileOwner($profile);
        $data = $this->validatedProfileData($request);
        $data['min_ram_gb'] = null;
        $data['active'] = $request->boolean('active');
        foreach ([
            'notify_new_listings',
            'notify_price_drops',
            'notify_search_errors',
            'notify_profile_matches',
            'notify_shared_price_changes',
        ] as $notificationPreference) {
            $data[$notificationPreference] = $request->exists($notificationPreference)
                ? $request->boolean($notificationPreference)
                : $profile->{$notificationPreference};
        }
        $data['notification_frequency'] = $request->input('notification_frequency', $profile->notification_frequency);
        $previousPriceAlertThreshold = $profile->price_alert_threshold;
        $changedFields = collect($data)
            ->filter(fn (mixed $value, string $field): bool => $profile->getAttribute($field) !== $value)
            ->keys()
            ->values()
            ->all();
        $profile->update($data);
        if ($previousPriceAlertThreshold !== $profile->price_alert_threshold) {
            $profile->listings()->withTrashed()->update(['price_threshold_notified_at' => null]);
        }

        $this->recordActivity(
            $profile,
            'updated',
            "Изменён профиль поиска «{$profile->name}».",
            ['changed_fields' => $changedFields],
        );

        return to_route('profiles.index')->with('success', 'Профиль поиска обновлён.');
    }

    public function destroy(Request $request, SearchProfile $profile): RedirectResponse
    {
        $this->ensureProfileOwner($profile);
        $this->recordActivity(
            $profile,
            'deleted',
            "Удалён профиль поиска «{$profile->name}».",
        );
        $profile->delete();

        return to_route('profiles.index')->with('success', 'Профиль поиска и связанные с ним лоты удалены.');
    }

    public function parsePage(Request $request, SearchProfile $profile, EbayBrowseService $ebay, TelegramNotificationService $telegram): JsonResponse
    {
        $this->ensureProfileOwner($profile);
        $validated = $request->validate([
            'offset' => ['required', 'integer', 'min:0', 'max:9999'],
            'run_id' => ['required', 'uuid'],
            'imported_total' => ['required', 'integer', 'min:0', 'max:999999999'],
            'total_found' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'price_min_cents' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'price_max_cents' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'has_pending_partitions' => ['sometimes', 'boolean'],
        ]);

        $runId = $validated['run_id'];
        $offset = (int) $validated['offset'];

        if (
            $offset === 0
            && ($validated['price_min_cents'] ?? null) === null
            && ($validated['price_max_cents'] ?? null) === null
        ) {
            $this->recordActivity(
                $profile,
                'parse_started',
                "Запущен парсинг профиля «{$profile->name}».",
                ['query' => $profile->query],
                $runId,
            );
        }

        try {
            $result = $ebay->importPage(
                $profile,
                $offset,
                isset($validated['price_min_cents']) ? (int) $validated['price_min_cents'] : null,
                isset($validated['price_max_cents']) ? (int) $validated['price_max_cents'] : null,
            );

            $hasPendingPartitions = (bool) ($validated['has_pending_partitions'] ?? false);
            if ($result['done'] && $result['next_partitions'] === [] && ! $hasPendingPartitions) {
                $imported = (int) $validated['imported_total'] + $result['imported'];
                $totalFound = (int) ($validated['total_found'] ?? $result['found']);
                $this->recordActivity(
                    $profile,
                    $result['unprocessed_count'] === 0 ? 'parse_completed' : 'parse_failed',
                    $result['unprocessed_count'] === 0
                        ? "Парсинг профиля «{$profile->name}» завершён: найдено {$totalFound}, сохранено или обновлено {$imported} товаров."
                        : "Парсинг профиля «{$profile->name}» завершён не полностью: {$result['unprocessed_count']} товаров eBay не удалось получить из-за лимита результатов с одинаковой ценой.",
                    [
                        'found' => $totalFound,
                        'processable' => $result['processable'],
                        'imported' => $imported,
                        'new_listings' => $result['new_listings'],
                        'unprocessed' => $result['unprocessed_count'],
                    ],
                    $runId,
                );

                if ($result['unprocessed_count'] === 0) {
                    $profile->update(['last_synced_at' => now()]);
                }
            }

            return response()->json($result);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);
            $this->recordActivity(
                $profile,
                'parse_failed',
                "Ошибка парсинга профиля «{$profile->name}» на странице с отступом {$offset}.",
                ['offset' => $offset],
                $runId,
            );
            $telegram->sendSearchFailure($profile->user, $profile, $exception->getMessage());

            return response()->json([
                'message' => 'Не удалось выполнить поиск eBay. Проверьте подключение и настройки API.',
            ], 502);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProfileData(Request $request): array
    {
        $multipleAspectFields = [
            'brand',
            'processor',
            'ram_size',
            'storage_capacity',
            'screen_size',
            'gpu',
        ];

        foreach ($multipleAspectFields as $field) {
            $value = $request->input($field);
            if (is_string($value) && $value !== '') {
                $value = [$value];
            }

            if (is_array($value) && in_array(self::ANY_FILTER_VALUE, $value, true)) {
                $value = [];
            }

            if (is_array($value)) {
                $request->merge([$field => $value]);
            }
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'query' => ['required', 'string', 'max:255'],
            'schedule_time' => ['sometimes', 'required', 'date_format:H:i'],
            'schedule_interval_hours' => ['sometimes', 'required', 'integer', 'between:1,24'],
            'condition' => ['nullable', 'in:NEW,USED,REFURBISHED'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'price_alert_threshold' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notify_new_listings' => ['sometimes', 'boolean'],
            'notify_price_drops' => ['sometimes', 'boolean'],
            'notify_search_errors' => ['sometimes', 'boolean'],
            'notify_profile_matches' => ['sometimes', 'boolean'],
            'notify_shared_price_changes' => ['sometimes', 'boolean'],
            'notification_frequency' => ['sometimes', 'required', Rule::in(['immediate', 'daily'])],
            'max_price' => [
                'nullable',
                'integer',
                'min:1',
                ...($request->filled('min_price') ? ['gte:min_price'] : []),
            ],
        ];
        $aspectFields = [
            'brand',
            'processor',
            'ram_size',
            'storage_capacity',
            'screen_size',
            'gpu',
            'operating_system',
        ];
        $selectedFields = collect($aspectFields)->filter(function (string $field) use ($request): bool {
            $value = $request->input($field);

            return is_array($value) ? $value !== [] : $request->filled($field);
        });

        if ($selectedFields->isNotEmpty()) {
            $options = $this->taxonomy->laptopAspectOptions();
            foreach ($selectedFields as $field) {
                if (in_array($field, $multipleAspectFields, true)) {
                    $rules[$field] = ['sometimes', 'array'];
                    $rules["{$field}.*"] = [
                        'required',
                        'string',
                        'distinct',
                        Rule::in($options[$field]['values']),
                    ];
                } else {
                    $rules[$field] = [
                        'nullable',
                        'string',
                        Rule::in($options[$field]['values']),
                    ];
                }
            }
        }

        $validated = $request->validate($rules);

        $validated = array_merge([
            'condition' => null,
            'min_price' => null,
            'max_price' => null,
            'price_alert_threshold' => null,
            'notify_new_listings' => true,
            'notify_price_drops' => true,
            'notify_search_errors' => true,
            'notify_profile_matches' => true,
            'notify_shared_price_changes' => true,
            'notification_frequency' => 'immediate',
            'brand' => null,
            'brands' => [],
            'processor' => null,
            'processors' => [],
            'ram_size' => null,
            'ram_sizes' => [],
            'storage_capacity' => null,
            'storage_capacities' => [],
            'screen_size' => null,
            'screen_sizes' => [],
            'gpu' => null,
            'gpus' => [],
            'operating_system' => null,
        ], $validated);

        foreach ([
            'brand' => 'brands',
            'processor' => 'processors',
            'ram_size' => 'ram_sizes',
            'storage_capacity' => 'storage_capacities',
            'screen_size' => 'screen_sizes',
            'gpu' => 'gpus',
        ] as $field => $storageField) {
            $values = array_values(array_unique($validated[$field] ?? []));
            $validated[$storageField] = $values;
            $validated[$field] = count($values) === 1 ? $values[0] : null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function recordActivity(
        SearchProfile $profile,
        string $action,
        string $description,
        ?array $metadata = null,
        ?string $runId = null,
        ?int $pid = null,
    ): void {
        SearchProfileActivity::query()->create([
            'search_profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'profile_owner_id' => $profile->user_id,
            'profile_name' => $profile->name,
            'action' => $action,
            'description' => $description,
            'run_id' => $runId,
            'pid' => $pid,
            'metadata' => $metadata,
        ]);
    }

    public function recentActivities(SearchProfile $profile): JsonResponse
    {
        $this->ensureProfileOwner($profile);

        $activities = $profile->activities()
            ->whereIn('action', ['parse_started', 'parse_completed', 'parse_failed'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'activities' => $activities->map(fn ($activity) => [
                'date' => $activity->created_at->format('d.m.Y H:i'),
                'action' => match ($activity->action) {
                    'parse_started' => 'Запуск',
                    'parse_completed' => 'Завершён',
                    'parse_failed' => 'Ошибка',
                    default => $activity->action,
                },
                'found' => data_get($activity->metadata, 'found'),
                'imported' => data_get($activity->metadata, 'imported'),
                'new_listings' => data_get($activity->metadata, 'new_listings'),
                'description' => $activity->description,
            ]),
        ]);
    }

    public function getListings(SearchProfile $profile): JsonResponse
    {
        $this->ensureProfileOwner($profile);

        $listings = $profile->profileListings()
            ->whereNull('laptop_listings.deleted_at')
            ->orderBy('laptop_listings.created_at', 'desc')
            ->paginate(25);

        return response()->json([
            'listings' => $listings->items(),
            'total' => $listings->total(),
            'per_page' => $listings->perPage(),
            'current_page' => $listings->currentPage(),
            'last_page' => $listings->lastPage(),
        ]);
    }

    private function ensureProfileOwner(SearchProfile $profile): void
    {
        abort_unless((string) $profile->user_id === (string) auth()->id(), 404);
    }
}
