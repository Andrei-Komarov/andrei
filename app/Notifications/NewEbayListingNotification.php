<?php

namespace App\Notifications;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use Illuminate\Notifications\Notification;

class NewEbayListingNotification extends Notification
{
    public function __construct(
        private LaptopListing $listing,
        private SearchProfile $profile,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'profile_id' => $this->profile->id,
            'profile_name' => $this->profile->name,
            'title' => $this->listing->title,
            'item_url' => $this->listing->item_url,
            'image_url' => $this->listing->image_url,
            'total_price' => (string) $this->listing->total_price,
            'currency' => $this->listing->currency,
        ];
    }
}
