<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class EbayApiClient
{
    public function client(): PendingRequest
    {
        $timeout = (int) (SiteSetting::query()->value('ebay_request_timeout') ?? 30);

        return Http::withToken($this->token())
            ->retry([1000, 2000, 5000, 10000], 4, fn (Throwable $exception): bool => $exception instanceof ConnectionException)
            ->timeout($timeout)
            ->withHeader('X-EBAY-C-MARKETPLACE-ID', $this->getMarketplaceId());
    }

    /**
     * @return array<string, mixed>
     */
    public function itemDetails(string $itemId): array
    {
        $response = $this->client()
            ->retry([2000, 4000, 8000], 3, function (Throwable $exception): bool {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                if ($exception instanceof RequestException) {
                    $httpResponse = $exception->response;

                    return $httpResponse !== null && $httpResponse->status() === 429;
                }

                return false;
            })
            ->get($this->getApiBaseUrl().'/buy/browse/v1/item/'.rawurlencode($itemId))
            ->throw();

        $details = $response->json();

        if (! is_array($details)) {
            throw new RuntimeException('eBay returned invalid item details.');
        }

        return $details;
    }

    private function token(): string
    {
        $cacheMinutes = (int) (SiteSetting::query()->value('ebay_token_cache_minutes') ?? 110);

        return Cache::remember('ebay.application-token', now()->addMinutes($cacheMinutes), function (): string {
            $clientId = $this->getClientId();
            $clientSecret = $this->getClientSecret();

            if ($clientId === '' || $clientSecret === '') {
                throw new RuntimeException('eBay API credentials are not configured.');
            }

            $response = Http::asForm()
                ->retry([1000, 2000, 5000, 10000], 4, fn (Throwable $exception): bool => $exception instanceof ConnectionException)
                ->withBasicAuth($clientId, $clientSecret)
                ->post($this->getIdentityBaseUrl().'/identity/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                    'scope' => 'https://api.ebay.com/oauth/api_scope',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('eBay OAuth failed: '.$response->body());
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('eBay OAuth response did not contain an access token.');
            }

            return $token;
        });
    }

    protected function getClientId(): string
    {
        $setting = SiteSetting::query()->first();

        return (string) ($setting->ebay_client_id ?? config('services.ebay.client_id', ''));
    }

    protected function getClientSecret(): string
    {
        $setting = SiteSetting::query()->first();

        return (string) ($setting->ebay_client_secret ?? config('services.ebay.client_secret', ''));
    }

    protected function getMarketplaceId(): string
    {
        $setting = SiteSetting::query()->first();

        return (string) ($setting->ebay_marketplace_id ?? config('services.ebay.marketplace_id', 'EBAY_US'));
    }

    protected function getApiBaseUrl(): string
    {
        $setting = SiteSetting::query()->first();

        return (string) ($setting->ebay_api_base_url ?? config('services.ebay.api_base_url', 'https://api.ebay.com'));
    }

    protected function getIdentityBaseUrl(): string
    {
        $setting = SiteSetting::query()->first();

        return (string) ($setting->ebay_identity_base_url ?? config('services.ebay.identity_base_url', 'https://api.ebay.com'));
    }
}
