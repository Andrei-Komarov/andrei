<?php

use App\Models\LaptopListing;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old composite unique index
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->dropUnique(['search_profile_id', 'ebay_item_id']);
        });

        // Populate the listing_profile pivot table from existing data
        LaptopListing::chunkById(100, function ($listings) {
            $records = $listings->map(function ($listing) {
                return [
                    'laptop_listing_id' => $listing->id,
                    'search_profile_id' => $listing->search_profile_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->values()->all();

            if (! empty($records)) {
                DB::table('listing_profile')->insert($records);
            }
        });

        // Consolidate duplicates: keep the oldest listing per ebay_item_id per user,
        // force-delete the rest. All profiles are already linked via pivot table.
        DB::transaction(function () {
            // Find all duplicate ebay_item_ids (including soft-deleted)
            $duplicates = DB::table('laptop_listings as ll')
                ->join('search_profiles as sp', 'll.search_profile_id', '=', 'sp.id')
                ->select('ll.ebay_item_id', 'sp.user_id', DB::raw('MIN(ll.id) as keep_id'))
                ->groupBy('ll.ebay_item_id', 'sp.user_id')
                ->havingRaw('COUNT(*) > 1')
                ->cursor();

            foreach ($duplicates as $dup) {
                // Get all listing IDs for this ebay_item_id + user (except the one to keep)
                $allIds = DB::table('laptop_listings as ll')
                    ->join('search_profiles as sp', 'll.search_profile_id', '=', 'sp.id')
                    ->where('sp.user_id', $dup->user_id)
                    ->where('ll.ebay_item_id', $dup->ebay_item_id)
                    ->where('ll.id', '!=', $dup->keep_id)
                    ->pluck('ll.id')
                    ->all();

                if (! empty($allIds)) {
                    // Force delete duplicates (permanent removal)
                    DB::table('laptop_listings')->whereIn('id', $allIds)->delete();
                }
            }
        });

        // Now create the global unique index
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->unique('ebay_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptop_listings', function (Blueprint $table) {
            $table->dropUnique('laptop_listings_ebay_item_id_unique');
            $table->unique(['search_profile_id', 'ebay_item_id']);
        });

        DB::table('listing_profile')->truncate();
    }
};
