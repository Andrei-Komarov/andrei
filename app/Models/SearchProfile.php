<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchProfile extends Model
{
    use HasFactory;

    protected function getDefaultAttributes(): array
    {
        $defaults = [
            'notify_new_listings' => true,
            'notify_price_drops' => true,
            'notify_search_errors' => true,
            'notify_profile_matches' => true,
            'notify_shared_price_changes' => true,
            'notification_frequency' => 'immediate',
        ];

        try {
            $settings = SiteSetting::query()->first();
            if ($settings) {
                $defaults['notify_new_listings'] = $settings->default_notify_new_listings ?? true;
                $defaults['notify_price_drops'] = $settings->default_notify_price_drops ?? true;
                $defaults['notify_search_errors'] = $settings->default_notify_search_errors ?? true;
                $defaults['notify_profile_matches'] = $settings->default_notify_profile_matches ?? true;
                $defaults['notify_shared_price_changes'] = $settings->default_notify_shared_price_changes ?? true;
                $defaults['notification_frequency'] = $settings->default_notification_frequency ?? 'immediate';
            }
        } catch (\Throwable) {
            // Database not available, use hardcoded defaults
        }

        return $defaults;
    }

    protected $attributes = [
        'notify_new_listings' => true,
        'notify_price_drops' => true,
        'notify_search_errors' => true,
        'notify_profile_matches' => true,
        'notify_shared_price_changes' => true,
        'notification_frequency' => 'immediate',
    ];

    protected $fillable = [
        'name',
        'user_id',
        'query',
        'condition',
        'min_price',
        'max_price',
        'price_alert_threshold',
        'notify_new_listings',
        'notify_price_drops',
        'notify_search_errors',
        'notify_profile_matches',
        'notify_shared_price_changes',
        'notification_frequency',
        'brand',
        'brands',
        'processor',
        'processors',
        'ram_size',
        'ram_sizes',
        'min_ram_gb',
        'storage_capacity',
        'storage_capacities',
        'screen_size',
        'screen_sizes',
        'gpu',
        'gpus',
        'operating_system',
        'active',
        'schedule_time',
        'last_scheduled_date',
        'schedule_interval_hours',
        'last_scheduled_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_scheduled_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'processors' => 'array',
            'brands' => 'array',
            'ram_sizes' => 'array',
            'storage_capacities' => 'array',
            'screen_sizes' => 'array',
            'gpus' => 'array',
            'price_alert_threshold' => 'decimal:2',
            'notify_new_listings' => 'boolean',
            'notify_price_drops' => 'boolean',
            'notify_search_errors' => 'boolean',
            'notify_profile_matches' => 'boolean',
            'notify_shared_price_changes' => 'boolean',
        ];
    }

    /**
     * Listings that belong directly to this profile (legacy relationship).
     */
    public function listings(): HasMany
    {
        return $this->hasMany(LaptopListing::class);
    }

    /**
     * All listings found in this profile (via pivot table).
     */
    public function profileListings(): BelongsToMany
    {
        return $this->belongsToMany(LaptopListing::class, 'listing_profile', 'search_profile_id', 'laptop_listing_id')->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notificationEvents(): HasMany
    {
        return $this->hasMany(SearchProfileNotificationEvent::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SearchProfileActivity::class);
    }
}
