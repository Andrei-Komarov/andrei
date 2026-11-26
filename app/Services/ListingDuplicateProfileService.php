<?php

namespace App\Services;

use App\Models\LaptopListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ListingDuplicateProfileService
{
    /**
     * @param  array<int, int>  $listingIds
     * @return Collection<int, EloquentCollection<int, LaptopListing>>
     */
    public function listingGroupsForListings(User $user, array $listingIds): Collection
    {
        if ($listingIds === []) {
            return collect();
        }

        // With deduplication, each listing is unique per ebay_item_id.
        // Return each listing grouped by itself with all its profiles loaded.
        $selectedListings = LaptopListing::query()
            ->with('searchProfiles:id,name,user_id')
            ->whereIn('id', $listingIds)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $user->id))
            ->get();

        if ($selectedListings->isEmpty()) {
            return collect();
        }

        return $selectedListings->mapWithKeys(fn (LaptopListing $listing): array => [
            $listing->id => collect([$listing]),
        ]);
    }

    /**
     * @param  array<int, int>  $listingIds
     * @return Collection<int, list<string>>
     */
    public function otherProfilesForListings(User $user, array $listingIds): Collection
    {
        if ($listingIds === []) {
            return collect();
        }

        $listings = LaptopListing::query()
            ->with('searchProfiles:id,name')
            ->whereIn('id', $listingIds)
            ->whereHas('searchProfiles', fn ($query) => $query->where('search_profiles.user_id', $user->id))
            ->get();

        return $listings->mapWithKeys(function (LaptopListing $listing) use ($user): array {
            $profileNames = $listing->searchProfiles
                ->where('user_id', $user->id)
                ->pluck('name')
                ->all();

            // Return all profiles except the first one (primary)
            return [
                $listing->id => array_slice($profileNames, 1),
            ];
        });
    }
}
