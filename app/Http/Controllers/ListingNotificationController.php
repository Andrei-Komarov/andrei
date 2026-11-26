<?php

namespace App\Http\Controllers;

use App\Services\ListingDuplicateProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingNotificationController extends Controller
{
    public function index(Request $request, ListingDuplicateProfileService $duplicateProfiles): View
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->paginate(25);

        return view('notifications.index', [
            'notifications' => $notifications,
            'duplicateProfilesByListingId' => $duplicateProfiles->otherProfilesForListings(
                $request->user(),
                $notifications->getCollection()
                    ->map(fn ($notification): int => (int) data_get($notification->data, 'listing_id'))
                    ->filter()
                    ->values()
                    ->all(),
            ),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()
            ->unreadNotifications()
            ->whereKey($notification)
            ->firstOrFail();

        $userNotification->markAsRead();

        return redirect()->route('notifications.index')->with('success', 'Уведомление отмечено как прочитанное.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return redirect()->route('notifications.index')->with('success', 'Все уведомления отмечены как прочитанные.');
    }
}
