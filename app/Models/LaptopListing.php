<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaptopListing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['search_profile_id', 'ebay_item_id', 'title', 'item_url', 'image_url', 'condition', 'price', 'currency', 'shipping_price', 'shipping_type', 'total_price', 'seller', 'seller_feedback', 'seller_description', 'cpu', 'ram_gb', 'storage', 'screen', 'gpu', 'specifications', 'raw_payload', 'status', 'excluded', 'excluded_reason', 'checked', 'seen_at', 'sold_at', 'price_threshold_notified_at'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'shipping_price' => 'decimal:2', 'total_price' => 'decimal:2', 'seller_feedback' => 'decimal:2', 'specifications' => 'array', 'raw_payload' => 'array', 'excluded' => 'boolean', 'checked' => 'boolean', 'seen_at' => 'datetime', 'sold_at' => 'datetime', 'price_threshold_notified_at' => 'datetime'];
    }

    public function searchProfile(): BelongsTo
    {
        return $this->belongsTo(SearchProfile::class);
    }

    /**
     * All search profiles where this listing was found.
     */
    public function searchProfiles(): BelongsToMany
    {
        return $this->belongsToMany(SearchProfile::class, 'listing_profile', 'laptop_listing_id', 'search_profile_id')->withTimestamps();
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(LaptopListingPriceHistory::class);
    }

    public function profitabilityCalculations(): HasMany
    {
        return $this->hasMany(ProfitabilityCalculation::class, 'listing_id');
    }

    /**
     * @return list<string>
     */
    public function galleryImageUrls(): array
    {
        $imageUrls = collect([
            $this->image_url,
            ...collect(data_get($this->raw_payload, 'thumbnailImages', []))->pluck('imageUrl')->all(),
            ...collect(data_get($this->raw_payload, 'additionalImages', []))->pluck('imageUrl')->all(),
        ])
            ->filter(fn (mixed $imageUrl): bool => is_string($imageUrl)
                && in_array(parse_url($imageUrl, PHP_URL_SCHEME), ['http', 'https'], true))
            ->map(fn (string $imageUrl): string => preg_replace(
                '~/s-l\d+(?=\.[a-z0-9]+(?:\?|$))~i',
                '/s-l1600',
                $imageUrl,
            ) ?? $imageUrl)
            ->unique()
            ->values();

        return $imageUrls->all();
    }
}
