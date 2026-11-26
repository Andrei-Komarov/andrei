<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelegramNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_validate_and_clear_their_telegram_chat_id(): void
    {
        $this->withoutVite();
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Уведомления в Telegram')
            ->assertSee('Сохранить Chat ID')
            ->assertSee('Chat ID');

        $this->patch(route('admin.settings.telegram.update'), ['telegram_chat_id' => '-1001234567890'])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('success');
        $this->assertSame('-1001234567890', $admin->fresh()->telegram_chat_id);

        $this->from(route('admin.settings'))->patch(route('admin.settings.telegram.update'), [
            'telegram_chat_id' => 'not-a-chat-id',
        ])->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('telegram_chat_id');
        $this->assertSame('-1001234567890', $admin->fresh()->telegram_chat_id);

        $this->patch(route('admin.settings.telegram.update'), ['telegram_chat_id' => ''])
            ->assertRedirect(route('admin.settings'));
        $this->assertNull($admin->fresh()->telegram_chat_id);
    }

    public function test_new_listing_telegram_message_includes_price_and_item_link(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'ThinkPad',
        ]);
        $listing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'ThinkPad T14',
            'item_url' => 'https://www.ebay.com/itm/123',
            'total_price' => 475.50,
            'currency' => 'USD',
        ]);

        app(TelegramNotificationService::class)->sendNewListings($user, [$listing], $profile);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/bottest-bot-token/sendMessage')
            && $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'ThinkPad T14')
            && str_contains($request->data()['text'], 'USD 475.50')
            && str_contains($request->data()['text'], 'https://www.ebay.com/itm/123'));
    }

    public function test_search_failure_is_sent_to_telegram_and_delivery_failures_are_logged(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 200),
        ]);
        Log::spy();
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'ThinkPad',
            'query' => 'ThinkPad T14',
        ]);

        app(TelegramNotificationService::class)->sendSearchFailure($user, $profile, 'eBay unavailable');

        Http::assertSent(fn (Request $request): bool => $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'Ошибка поиска')
            && str_contains($request->data()['text'], 'ThinkPad T14')
            && str_contains($request->data()['text'], 'eBay unavailable'));
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Telegram notification could not be delivered.', [
                'user_id' => $user->id,
                'exception_type' => \RuntimeException::class,
            ]);
    }

    public function test_user_without_a_telegram_chat_id_does_not_trigger_a_request(): void
    {
        Http::fake();
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create();
        $profile = SearchProfile::factory()->create(['user_id' => $user->id]);
        $listing = LaptopListing::factory()->create(['search_profile_id' => $profile->id]);

        app(TelegramNotificationService::class)->sendNewListings($user, [$listing], $profile);

        Http::assertNothingSent();
    }

    public function test_daily_digest_collects_enabled_events_and_marks_them_sent(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Daily ThinkPad',
            'query' => 'ThinkPad T14',
            'notification_frequency' => 'daily',
        ]);
        $listing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'title' => 'Daily ThinkPad T14',
            'currency' => 'USD',
            'total_price' => 450,
            'item_url' => 'https://www.ebay.com/itm/daily-thinkpad',
        ]);
        $notifications = app(TelegramNotificationService::class);

        $notifications->sendNewListings($user, [$listing], $profile);
        $notifications->sendPriceDrop($user, $profile, $listing, 500, 400, true);
        $notifications->sendProfileMatch($user, $profile, $listing);
        $notifications->sendSharedPriceChange($user, $profile, $listing, 500, 'USD');
        $notifications->sendSearchFailure($user, $profile, 'eBay is unavailable');

        Http::assertNothingSent();
        $this->assertDatabaseCount('search_profile_notification_events', 5);

        $notifications->sendDailyDigest($profile);

        Http::assertSentCount(1);
        $request = Http::recorded()->first()[0];
        $message = $request->data()['text'];
        $this->assertStringContainsString('📋 Ежедневная сводка профиля', $message);
        $this->assertStringContainsString('Daily ThinkPad T14', $message);
        $this->assertStringContainsString('Было: USD 500.00', $message);
        $this->assertStringContainsString('Цена опустилась ниже целевой: USD 400.00', $message);
        $this->assertStringContainsString('найден в профиле «Daily ThinkPad»', $message);
        $this->assertStringContainsString('Цена изменилась в нескольких профилях', $message);
        $this->assertStringContainsString('Было: USD 500.00', $message);
        $this->assertStringContainsString('Ошибка поиска', $message);
        $this->assertStringContainsString('eBay is unavailable', $message);
        $this->assertDatabaseCount('search_profile_notification_events', 5);
        $this->assertDatabaseMissing('search_profile_notification_events', ['sent_at' => null]);

        $notifications->sendDailyDigest($profile);
        Http::assertSentCount(1);
    }

    public function test_profile_notification_preferences_suppress_disabled_event_types(): void
    {
        Http::fake();
        config(['services.telegram.bot_token' => 'test-bot-token']);
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);
        $profile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'notify_new_listings' => false,
            'notify_price_drops' => false,
            'notify_search_errors' => false,
            'notify_profile_matches' => false,
            'notify_shared_price_changes' => false,
        ]);
        $listing = LaptopListing::factory()->create(['search_profile_id' => $profile->id]);
        $notifications = app(TelegramNotificationService::class);

        $notifications->sendNewListings($user, [$listing], $profile);
        $notifications->sendPriceDrop($user, $profile, $listing, 500, null, false);
        $notifications->sendProfileMatch($user, $profile, $listing);
        $notifications->sendSharedPriceChange($user, $profile, $listing, 500, 'USD');
        $notifications->sendSearchFailure($user, $profile, 'eBay unavailable');
        $notifications->sendDailyDigest($profile);

        Http::assertNothingSent();
        $this->assertDatabaseCount('search_profile_notification_events', 0);
    }
}
