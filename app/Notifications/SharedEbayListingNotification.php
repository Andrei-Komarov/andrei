<?php

namespace App\Notifications;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use Illuminate\Notifications\Notification;

class SharedEbayListingNotification extends Notification
{
    /**
     * @param  array<int, string>  $profileNames
     */
    public function __construct(
        private string $alertType,
        private LaptopListing $listing,
        private SearchProfile $profile,
        private array $profileNames,
        private ?float $previousPrice = null,
        private ?string $previousCurrency = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, int|string|float|list<string>|null>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'smart_alert_type' => $this->alertType,
            'listing_id' => $this->listing->id,
            'profile_id' => $this->profile->id,
            'profile_name' => $this->profile->name,
            'profile_names' => $this->profileNames,
            'title' => $this->listing->title,
            'item_url' => $this->listing->item_url,
            'image_url' => $this->listing->image_url,
            'total_price' => (string) $this->listing->total_price,
            'currency' => $this->listing->currency,
            'previous_price' => $this->previousPrice,
            'previous_currency' => $this->previousCurrency,
        ];
    }
}
