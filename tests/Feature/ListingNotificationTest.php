<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\User;
use App\Notifications\NewEbayListingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_notification_popup_lists_all_unread_news_for_the_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->for($user)->create(['name' => 'My laptop profile']);
        $unreadListings = LaptopListing::factory()
            ->for($profile)
            ->count(26)
            ->sequence(fn ($sequence): array => [
                'title' => 'Popup laptop '.$sequence->index,
                'item_url' => "https://www.ebay.com/itm/popup-laptop-{$sequence->index}",
            ])
            ->create();
        $unreadListings->each(fn (LaptopListing $listing) => $user->notify(new NewEbayListingNotification($listing, $profile)));

        $readListing = LaptopListing::factory()->for($profile)->create(['title' => 'Read popup laptop']);
        $readNotification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => NewEbayListingNotification::class,
            'data' => ['title' => $readListing->title],
            'read_at' => now(),
        ]);
        $otherUser = User::factory()->create();
        $otherProfile = SearchProfile::factory()->for($otherUser)->create();
        $otherListing = LaptopListing::factory()->for($otherProfile)->create(['title' => 'Private popup laptop']);
        $otherUser->notify(new NewEbayListingNotification($otherListing, $otherProfile));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Последние уведомления')
            ->assertSee('Все новости')
            ->assertSee('data-notification-menu', false)
            ->assertSee('Popup laptop 0')
            ->assertSee('Popup laptop 25')
            ->assertDontSee('Read popup laptop')
            ->assertDontSee('Private popup laptop')
            ->assertSee('data-unread-notification-count', false)
            ->assertSee('26', false)
            ->assertSee('group-hover:block', false)
            ->assertSee('group-focus-within:block', false)
            ->assertSee('group-open:block', false);

        $this->assertNotNull($readNotification->fresh()->read_at);
    }

    public function test_user_can_mark_all_their_unread_notifications_as_read(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->for($user)->create();
        $firstListing = LaptopListing::factory()->for($profile)->create();
        $secondListing = LaptopListing::factory()->for($profile)->create();
        $user->notify(new NewEbayListingNotification($firstListing, $profile));
        $user->notify(new NewEbayListingNotification($secondListing, $profile));
        $unreadNotifications = $user->unreadNotifications()->get();

        $readNotification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => NewEbayListingNotification::class,
            'data' => ['title' => 'Already read'],
            'read_at' => now(),
        ]);
        $otherUser = User::factory()->create();
        $otherUserProfile = SearchProfile::factory()->for($otherUser)->create();
        $otherListing = LaptopListing::factory()->for($otherUserProfile)->create();
        $otherUser->notify(new NewEbayListingNotification($otherListing, $otherUserProfile));
        $otherUserNotification = $otherUser->unreadNotifications()->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Отметить все прочитанными')
            ->assertSee('Объявления, найденные вашими профилями поиска');

        $content = $response->getContent();
        $headerPosition = strpos($content, '<h1 class="text-2xl font-bold">Новые товары</h1>');
        $markAllPosition = strpos($content, 'Отметить все прочитанными');
        $notificationPosition = strpos($content, 'data-notification-list-item');
        $this->assertNotFalse($headerPosition);
        $this->assertNotFalse($markAllPosition);
        $this->assertNotFalse($notificationPosition);
        $this->assertLessThan($notificationPosition, $headerPosition);
        $this->assertLessThan($notificationPosition, $markAllPosition);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->patch(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', 'Все уведомления отмечены как прочитанные.');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        foreach ($unreadNotifications as $notification) {
            $this->assertNotNull($notification->fresh()->read_at);
        }
        $this->assertNotNull($readNotification->fresh()->read_at);
        $this->assertNull($otherUserNotification->fresh()->read_at);
    }

    public function test_guest_cannot_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->for($user)->create();
        $listing = LaptopListing::factory()->for($profile)->create();
        $user->notify(new NewEbayListingNotification($listing, $profile));

        $this->patch(route('notifications.read-all'))
            ->assertRedirect(route('login'));

        $this->assertSame(1, $user->unreadNotifications()->count());
    }
}
