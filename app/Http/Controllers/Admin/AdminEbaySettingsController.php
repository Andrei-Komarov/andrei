<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminEbaySettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.ebay.index', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateConnection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_client_id' => ['nullable', 'string', 'max:255'],
            'ebay_client_secret' => ['nullable', 'string', 'max:255'],
            'ebay_marketplace_id' => ['required', 'string', 'max:50'],
            'ebay_api_base_url' => ['required', 'string', 'url', 'max:255'],
            'ebay_identity_base_url' => ['required', 'string', 'url', 'max:255'],
            'ebay_sandbox_mode' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_client_id' => $validated['ebay_client_id'],
            'ebay_client_secret' => $validated['ebay_client_secret'],
            'ebay_marketplace_id' => $validated['ebay_marketplace_id'],
            'ebay_api_base_url' => rtrim($validated['ebay_api_base_url'], '/'),
            'ebay_identity_base_url' => rtrim($validated['ebay_identity_base_url'], '/'),
            'ebay_sandbox_mode' => $request->has('ebay_sandbox_mode'),
        ]);

        // Clear cached eBay token when credentials change
        if ($request->filled('ebay_client_id') || $request->filled('ebay_client_secret')) {
            Cache::forget('ebay.application-token');
        }

        return to_route('admin.ebay.index')->with('success', 'Настройки подключения к eBay API сохранены.');
    }

    public function delays(): View
    {
        return view('admin.ebay.delays', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateDelays(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_profile_delay_seconds' => ['required', 'integer', 'min:0', 'max:300'],
            'ebay_page_delay_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'ebay_filter_check_delay_seconds' => ['required', 'integer', 'min:0', 'max:60'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_profile_delay_seconds' => $validated['ebay_profile_delay_seconds'],
            'ebay_page_delay_seconds' => $validated['ebay_page_delay_seconds'],
            'ebay_filter_check_delay_seconds' => $validated['ebay_filter_check_delay_seconds'],
        ]);

        return to_route('admin.ebay.delays')->with('success', 'Настройки задержек eBay API сохранены.');
    }

    public function account(): View
    {
        return view('admin.ebay.account', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_client_id' => ['nullable', 'string', 'max:255'],
            'ebay_client_secret' => ['nullable', 'string', 'max:255'],
            'ebay_marketplace_id' => ['required', 'string', 'max:50'],
            'ebay_api_base_url' => ['required', 'string', 'url', 'max:255'],
            'ebay_identity_base_url' => ['required', 'string', 'url', 'max:255'],
            'ebay_sandbox_mode' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_client_id' => $validated['ebay_client_id'],
            'ebay_client_secret' => $validated['ebay_client_secret'],
            'ebay_marketplace_id' => $validated['ebay_marketplace_id'],
            'ebay_api_base_url' => rtrim($validated['ebay_api_base_url'], '/'),
            'ebay_identity_base_url' => rtrim($validated['ebay_identity_base_url'], '/'),
            'ebay_sandbox_mode' => $request->has('ebay_sandbox_mode'),
        ]);

        // Clear cached eBay token when credentials change
        if ($request->filled('ebay_client_id') || $request->filled('ebay_client_secret')) {
            Cache::forget('ebay.application-token');
        }

        return to_route('admin.ebay.account')->with('success', 'Настройки eBay аккаунта сохранены.');
    }

    public function advanced(): View
    {
        return view('admin.ebay.advanced', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateAdvanced(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_max_retries' => ['required', 'integer', 'min:0', 'max:20'],
            'ebay_base_delay_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'ebay_token_cache_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'ebay_enable_retry' => ['boolean'],
            'ebay_request_timeout' => ['required', 'integer', 'min:5', 'max:120'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_max_retries' => $validated['ebay_max_retries'],
            'ebay_base_delay_seconds' => $validated['ebay_base_delay_seconds'],
            'ebay_token_cache_minutes' => $validated['ebay_token_cache_minutes'],
            'ebay_enable_retry' => $request->has('ebay_enable_retry'),
            'ebay_request_timeout' => $validated['ebay_request_timeout'],
        ]);

        return to_route('admin.ebay.advanced')->with('success', 'Расширенные настройки eBay сохранены.');
    }

    public function extra(): View
    {
        return view('admin.ebay.extra', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateExtra(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_category_id' => ['required', 'string', 'in:175672,171215,0'],
            'ebay_sort_by' => ['required', 'string', 'in:price,RelevancePlus,EndTimeSoon,PricePlusShippingBuyerHi'],
            'ebay_ignored_sellers' => ['nullable', 'string', 'max:1000'],
            'ebay_seller_country' => ['nullable', 'string', 'max:10'],
            'ebay_free_shipping_only' => ['boolean'],
            'ebay_buy_it_now_only' => ['boolean'],
            'ebay_auction_only' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);

        // Parse ignored sellers from comma-separated string to JSON
        $ignoredSellers = null;
        if ($request->filled('ebay_ignored_sellers')) {
            $sellers = array_map('trim', explode(',', $validated['ebay_ignored_sellers']));
            $sellers = array_filter($sellers, fn ($s) => $s !== '');
            $ignoredSellers = count($sellers) > 0 ? json_encode($sellers) : null;
        }

        $siteSettings->update([
            'ebay_category_id' => $validated['ebay_category_id'],
            'ebay_sort_by' => $validated['ebay_sort_by'],
            'ebay_ignored_sellers' => $ignoredSellers,
            'ebay_seller_country' => $validated['ebay_seller_country'] ?: null,
            'ebay_free_shipping_only' => $request->has('ebay_free_shipping_only'),
            'ebay_buy_it_now_only' => $request->has('ebay_buy_it_now_only'),
            'ebay_auction_only' => $request->has('ebay_auction_only'),
        ]);

        return to_route('admin.ebay.extra')->with('success', 'Дополнительные настройки eBay сохранены.');
    }

    public function monitoring(): View
    {
        return view('admin.ebay.monitoring', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateMonitoring(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_stuck_threshold_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'ebay_auto_kill_hung' => ['boolean'],
            'ebay_429_delay_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_stuck_threshold_minutes' => $validated['ebay_stuck_threshold_minutes'],
            'ebay_auto_kill_hung' => $request->has('ebay_auto_kill_hung'),
            'ebay_429_delay_minutes' => $validated['ebay_429_delay_minutes'],
        ]);

        return to_route('admin.ebay.monitoring')->with('success', 'Настройки мониторинга импорта сохранены.');
    }

    public function updateApiLimit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ebay_api_limit_threshold_percent' => ['required', 'integer', 'min:50', 'max:99'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'ebay_api_limit_threshold_percent' => $validated['ebay_api_limit_threshold_percent'],
        ]);

        return to_route('admin.ebay.monitoring')->with('success', 'Настройки дневного лимита eBay API сохранены.');
    }
}
