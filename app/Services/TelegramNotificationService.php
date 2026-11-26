<?php

namespace App\Services;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileNotificationEvent;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramNotificationService
{
    private const MAX_MESSAGE_LENGTH = 3500;

    /**
     * @param  array<int, LaptopListing>  $listings
     */
    public function sendNewListings(?User $user, array $listings, SearchProfile $profile): void
    {
        if ($user === null || $listings === [] || ! $profile->notify_new_listings) {
            return;
        }

        if ($this->usesDailyDigest($profile)) {
            foreach ($listings as $listing) {
                $this->queueEvent($profile, 'new_listing', [
                    'title' => $listing->title,
                    'price' => (float) $listing->price,
                    'shipping_price' => (float) ($listing->shipping_price ?? 0),
                    'shipping_type' => $listing->shipping_type,
                    'currency' => $listing->currency,
                    'item_url' => $listing->item_url,
                ]);
            }

            return;
        }

        $prefix = "Новые товары из профиля «{$profile->name}»:";
        $message = $prefix;

        foreach ($listings as $listing) {
            $shippingText = $listing->shipping_type === 'CALCULATED'
                    ? 'CALCULATED'
                    : "{$listing->currency} ".number_format((float) ($listing->shipping_price ?? 0), 2, '.', ' ');
            $listingMessage = "🆕 {$listing->title}\nЦена: {$listing->currency} ".number_format((float) $listing->price, 2, '.', ' ')
                ."\nДоставка: {$shippingText}"
                ."\n{$listing->item_url}";
            $nextMessage = $message."\n\n".$listingMessage;

            if (mb_strlen($nextMessage) > self::MAX_MESSAGE_LENGTH && $message !== $prefix) {
                $this->deliver($user, $message);
                $message = $prefix."\n\n".$listingMessage;
            } else {
                $message = $nextMessage;
            }
        }

        if ($message !== $prefix) {
            $this->deliver($user, $message);
        }
    }

    public function sendSearchFailure(?User $user, SearchProfile $profile, string $error): void
    {
        if ($user === null || ! $profile->notify_search_errors) {
            return;
        }

        $payload = [
            'profile' => $profile->name,
            'query' => $profile->query,
            'error' => mb_substr($error, 0, 1000),
        ];

        if ($this->usesDailyDigest($profile)) {
            $this->queueEvent($profile, 'search_error', $payload);

            return;
        }

        $this->deliver($user, $this->formatEvent('search_error', $payload));
    }

    public function sendPriceDrop(
        ?User $user,
        SearchProfile $profile,
        LaptopListing $listing,
        float $previousPrice,
        ?float $targetPrice,
        bool $crossedTarget,
    ): void {
        if ($user === null || ! $profile->notify_price_drops) {
            return;
        }

        $payload = [
            'title' => $listing->title,
            'previous_price' => $previousPrice,
            'price' => (float) $listing->price,
            'shipping_price' => (float) ($listing->shipping_price ?? 0),
            'shipping_type' => $listing->shipping_type,
            'currency' => $listing->currency,
            'target_price' => $targetPrice,
            'crossed_target' => $crossedTarget,
            'item_url' => $listing->item_url,
        ];

        if ($this->usesDailyDigest($profile)) {
            $this->queueEvent($profile, 'price_drop', $payload);

            return;
        }

        $this->deliver($user, $this->formatEvent('price_drop', $payload));
    }

    public function sendProfileMatch(?User $user, SearchProfile $profile, LaptopListing $listing): void
    {
        if ($user === null || ! $profile->notify_profile_matches) {
            return;
        }

        $payload = [
            'title' => $listing->title,
            'price' => (float) $listing->price,
            'shipping_price' => (float) ($listing->shipping_price ?? 0),
            'shipping_type' => $listing->shipping_type,
            'currency' => $listing->currency,
            'item_url' => $listing->item_url,
            'profile' => $profile->name,
        ];

        if ($this->usesDailyDigest($profile)) {
            $this->queueEvent($profile, 'profile_match', $payload);

            return;
        }

        $this->deliver($user, $this->formatEvent('profile_match', $payload));
    }

    public function sendSharedPriceChange(
        ?User $user,
        SearchProfile $profile,
        LaptopListing $listing,
        float $previousPrice,
        ?string $previousCurrency,
    ): void {
        if ($user === null || ! $profile->notify_shared_price_changes) {
            return;
        }

        $payload = [
            'title' => $listing->title,
            'previous_price' => $previousPrice,
            'previous_currency' => $previousCurrency ?? $listing->currency,
            'price' => (float) $listing->price,
            'shipping_price' => (float) ($listing->shipping_price ?? 0),
            'shipping_type' => $listing->shipping_type,
            'currency' => $listing->currency,
            'item_url' => $listing->item_url,
        ];

        if ($this->usesDailyDigest($profile)) {
            $this->queueEvent($profile, 'shared_price_change', $payload);

            return;
        }

        $this->deliver($user, $this->formatEvent('shared_price_change', $payload));
    }

    public function sendDailyDigest(SearchProfile $profile): void
    {
        if (! $this->usesDailyDigest($profile) || ! $profile->user?->telegram_chat_id) {
            return;
        }

        $pendingEvents = $profile->notificationEvents()
            ->whereNull('sent_at')
            ->orderBy('id')
            ->get();

        if ($pendingEvents->isEmpty()) {
            return;
        }

        $user = $profile->user;
        $message = "📋 Ежедневная сводка профиля «{$profile->name}»:";
        $messageEventIds = [];

        foreach ($pendingEvents as $event) {
            $eventMessage = $this->formatEvent($event->event_type, $event->payload);
            $nextMessage = $message."\n\n".$eventMessage;

            if (mb_strlen($nextMessage) > self::MAX_MESSAGE_LENGTH && $messageEventIds !== []) {
                if (! $this->deliver($user, $message)) {
                    return;
                }

                $this->markEventsAsSent($messageEventIds);
                $message = "📋 Продолжение сводки профиля «{$profile->name}»:";
                $messageEventIds = [];
            }

            $message .= "\n\n".$eventMessage;
            $messageEventIds[] = $event->id;
        }

        if ($messageEventIds !== [] && $this->deliver($user, $message)) {
            $this->markEventsAsSent($messageEventIds);
        }
    }

    private function deliver(User $user, string $message): bool
    {
        if (! $user->telegram_chat_id) {
            return false;
        }

        $token = config('services.telegram.bot_token');

        try {
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Telegram bot token is not configured.');
            }

            $response = Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $message,
                    'disable_web_page_preview' => false,
                ])
                ->throw();

            if ($response->json('ok') !== true) {
                throw new RuntimeException('Telegram rejected the notification.');
            }

            return true;
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Log::error('Telegram notification could not be delivered.', [
                'user_id' => $user->id,
                'exception_type' => $exception::class,
            ]);

            return false;
        }
    }

    private function usesDailyDigest(SearchProfile $profile): bool
    {
        return $profile->notification_frequency === 'daily';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function queueEvent(SearchProfile $profile, string $eventType, array $payload): void
    {
        if (! $profile->user?->telegram_chat_id) {
            return;
        }

        $profile->notificationEvents()->create([
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function formatEvent(string $eventType, array $payload): string
    {
        $shippingLine = isset($payload['shipping_price'])
            ? "\nДоставка: ".($payload['shipping_type'] === 'CALCULATED' ? 'CALCULATED' : "{$payload['currency']} ".number_format((float) $payload['shipping_price'], 2, '.', ' '))
            : '';

        return match ($eventType) {
            'new_listing' => "🆕 {$payload['title']}\nЦена: {$payload['currency']} ".number_format((float) $payload['price'], 2, '.', ' ')
                .$shippingLine."\n{$payload['item_url']}",
            'profile_match' => "🔎 Уже известный лот найден в профиле «{$payload['profile']}»\nТовар: {$payload['title']}"
                ."\nЦена: {$payload['currency']} ".number_format((float) $payload['price'], 2, '.', ' ')
                .$shippingLine."\n{$payload['item_url']}",
            'price_drop' => $this->formatPriceDrop($payload),
            'shared_price_change' => "🔁 Цена изменилась в нескольких профилях\nТовар: {$payload['title']}"
                ."\nБыло: {$payload['previous_currency']} ".number_format((float) $payload['previous_price'], 2, '.', ' ')
                ."\nСтало: {$payload['currency']} ".number_format((float) $payload['price'], 2, '.', ' ')
                .(isset($payload['shipping_price']) ? "\nДоставка: ".($payload['shipping_type'] === 'CALCULATED' ? 'CALCULATED' : "{$payload['currency']} ".number_format((float) $payload['shipping_price'], 2, '.', ' ')) : '')
                ."\n{$payload['item_url']}",
            'search_error' => "⚠️ Ошибка поиска\nПрофиль: {$payload['profile']}\nЗапрос: {$payload['query']}\nПричина: ".mb_substr((string) $payload['error'], 0, 1000),
            default => throw new RuntimeException("Unknown profile notification event type: {$eventType}"),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function formatPriceDrop(array $payload): string
    {
        $message = "📉 Снизилась цена\nТовар: {$payload['title']}"
            ."\nБыло: {$payload['currency']} ".number_format((float) $payload['previous_price'], 2, '.', ' ')
            ."\nСтало: {$payload['currency']} ".number_format((float) $payload['price'], 2, '.', ' ');

        if (isset($payload['shipping_price'])) {
            $message .= "\nДоставка: ".($payload['shipping_type'] === 'CALCULATED' ? 'CALCULATED' : "{$payload['currency']} ".number_format((float) $payload['shipping_price'], 2, '.', ' '));
        }

        if (($payload['crossed_target'] ?? false) && ($payload['target_price'] ?? null) !== null) {
            $message .= "\nЦена опустилась ниже целевой: {$payload['currency']} ".number_format((float) $payload['target_price'], 2, '.', ' ');
        } elseif (($payload['target_price'] ?? null) !== null) {
            $message .= "\nЦелевая цена: {$payload['currency']} ".number_format((float) $payload['target_price'], 2, '.', ' ');
        }

        return $message."\n{$payload['item_url']}";
    }

    /**
     * @param  array<int, int>  $eventIds
     */
    private function markEventsAsSent(array $eventIds): void
    {
        SearchProfileNotificationEvent::query()
            ->whereIn('id', $eventIds)
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);
    }
}
