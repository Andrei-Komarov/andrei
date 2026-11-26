<?php

namespace App\Http\Controllers;

use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchSummaryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sort = $request->validate([
            'sort' => ['nullable', Rule::in([
                'profile_asc', 'profile_desc',
                'found_asc', 'found_desc',
                'new_listings_asc', 'new_listings_desc',
                'total_asc', 'total_desc',
                'watching_asc', 'watching_desc',
                'buy_asc', 'buy_desc',
                'last_run_asc', 'last_run_desc',
                'status_asc', 'status_desc',
            ])],
        ])['sort'] ?? 'profile_asc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $profiles = SearchProfile::query()
            ->where('user_id', $request->user()->id)
            ->withCount([
                'profileListings as listings_count' => fn ($query) => $query->whereNull('laptop_listings.deleted_at')->where('laptop_listings.excluded', false),
                'profileListings as watching_listings_count' => fn ($query) => $query->whereNull('laptop_listings.deleted_at')->where('laptop_listings.excluded', false)->where('laptop_listings.status', 'watching'),
                'profileListings as buy_listings_count' => fn ($query) => $query->whereNull('laptop_listings.deleted_at')->where('laptop_listings.excluded', false)->where('laptop_listings.status', 'buy'),
            ])
            ->orderBy('name')
            ->get();

        $latestActivityIds = SearchProfileActivity::query()
            ->selectRaw('MAX(id)')
            ->where('profile_owner_id', $request->user()->id)
            ->where('action', 'parse_completed')
            ->whereIn('search_profile_id', $profiles->modelKeys())
            ->groupBy('search_profile_id');
        $latestActivities = SearchProfileActivity::query()
            ->whereIn('id', $latestActivityIds)
            ->get()
            ->keyBy('search_profile_id');

        $now = now('Asia/Bishkek');
        $overdueProfiles = $profiles->filter(fn (SearchProfile $profile): bool => $profile->active
            && ($profile->last_synced_at === null
                || $profile->last_synced_at->copy()
                    ->addHours((int) $profile->schedule_interval_hours)
                    ->lessThanOrEqualTo($now)));
        $profiles = $profiles->sortBy(
            fn (SearchProfile $profile): mixed => match ($sortKey) {
                'found' => (int) data_get($latestActivities->get($profile->id)?->metadata, 'found', 0),
                'new_listings' => (int) data_get($latestActivities->get($profile->id)?->metadata, 'new_listings', 0),
                'total' => $profile->listings_count,
                'watching' => $profile->watching_listings_count,
                'buy' => $profile->buy_listings_count,
                'last_run' => $profile->last_synced_at?->timestamp ?? 0,
                'status' => ! $profile->active ? 0 : ($overdueProfiles->contains('id', $profile->id) ? 1 : 2),
                default => $profile->name,
            },
            SORT_NATURAL | SORT_FLAG_CASE,
            $sortDirection === 'desc',
        )->values();

        return view('search-summary', [
            'profiles' => $profiles,
            'sort' => $sort,
            'latestActivities' => $latestActivities,
            'foundCount' => $latestActivities->sum(fn (SearchProfileActivity $activity): int => (int) data_get($activity->metadata, 'found', 0)),
            'newListingCount' => $latestActivities->sum(fn (SearchProfileActivity $activity): int => (int) data_get($activity->metadata, 'new_listings', 0)),
            'listingCount' => $profiles->sum('listings_count'),
            'watchingCount' => $profiles->sum('watching_listings_count'),
            'buyCount' => $profiles->sum('buy_listings_count'),
            'overdueProfiles' => $overdueProfiles,
        ]);
    }
}
