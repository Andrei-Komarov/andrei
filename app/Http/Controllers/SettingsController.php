<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings', [
            'telegramBotConfigured' => filled(config('services.telegram.bot_token')),
            'telegramBotUsername' => config('services.telegram.bot_username'),
            'siteSettings' => SiteSetting::query()->findOrFail(1),
            'timezones' => DateTimeZone::listIdentifiers(DateTimeZone::ALL),
            'isDown' => file_exists(storage_path('framework/down')),
        ]);
    }

    public function updateProfitability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'in:KGS,USD'],
            'exchange_rate' => [
                'nullable',
                'required_if:currency,USD',
                'numeric',
                'decimal:0,4',
                'min:0.0001',
                'max:99999999.9999',
            ],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'profitability_currency' => $validated['currency'],
            'kgs_per_usd' => $validated['exchange_rate'] ?? $siteSettings->kgs_per_usd,
        ]);

        return to_route('admin.settings')->with('success', 'Настройки валюты рентабельности сохранены.');
    }

    public function updateTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => ['nullable', 'string', 'max:32', 'regex:/^-?[0-9]+$/'],
        ]);
        $admin = $request->user('admin');
        abort_unless($admin instanceof User, 403);

        $admin->update([
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
        ]);

        return to_route('admin.settings')->with('success', 'Настройки Telegram сохранены.');
    }

    public function updateTimezone(Request $request): RedirectResponse
    {
        $allTimezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        $validated = $request->validate([
            'site_timezone' => ['required', 'string', 'in:'.implode(',', $allTimezones)],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'site_timezone' => $validated['site_timezone'],
        ]);

        // Update the application timezone immediately
        config(['app.timezone' => $validated['site_timezone']]);
        date_default_timezone_set($validated['site_timezone']);

        return to_route('admin.settings')->with('success', 'Часовой пояс сайта обновлён.');
    }

    public function updateImportSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import_max_results' => ['required', 'integer', 'min:100', 'max:100000'],
            'import_page_size' => ['required', 'integer', 'min:10', 'max:200'],
            'default_notify_new_listings' => ['boolean'],
            'default_notify_price_drops' => ['boolean'],
            'default_notify_search_errors' => ['boolean'],
            'default_notify_profile_matches' => ['boolean'],
            'default_notify_shared_price_changes' => ['boolean'],
            'default_notification_frequency' => ['required', 'string', 'in:immediate,daily'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'import_max_results' => $validated['import_max_results'],
            'import_page_size' => $validated['import_page_size'],
            'default_notify_new_listings' => $request->has('default_notify_new_listings'),
            'default_notify_price_drops' => $request->has('default_notify_price_drops'),
            'default_notify_search_errors' => $request->has('default_notify_search_errors'),
            'default_notify_profile_matches' => $request->has('default_notify_profile_matches'),
            'default_notify_shared_price_changes' => $request->has('default_notify_shared_price_changes'),
            'default_notification_frequency' => $validated['default_notification_frequency'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки импорта сохранены.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appearance_logo' => ['nullable', 'image', 'max:2048'],
            'appearance_favicon' => ['nullable', 'image', 'max:512', 'mimes:ico,png'],
            'appearance_site_subtitle' => ['required', 'string', 'max:255'],
            'appearance_default_per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100, 200])],
            'appearance_default_theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);

        // Handle logo upload
        if ($request->hasFile('appearance_logo')) {
            // Delete old logo if exists
            if ($siteSettings->appearance_logo_path) {
                Storage::disk('public')->delete($siteSettings->appearance_logo_path);
            }
            $siteSettings->appearance_logo_path = $request->file('appearance_logo')->store('appearance', 'public');
        }

        // Handle favicon upload
        if ($request->hasFile('appearance_favicon')) {
            // Delete old favicon if exists
            if ($siteSettings->appearance_favicon_path) {
                Storage::disk('public')->delete($siteSettings->appearance_favicon_path);
            }
            $siteSettings->appearance_favicon_path = $request->file('appearance_favicon')->store('appearance', 'public');
        }

        // Handle remove logo
        if ($request->filled('remove_logo')) {
            if ($siteSettings->appearance_logo_path) {
                Storage::disk('public')->delete($siteSettings->appearance_logo_path);
            }
            $siteSettings->appearance_logo_path = null;
        }

        // Handle remove favicon
        if ($request->filled('remove_favicon')) {
            if ($siteSettings->appearance_favicon_path) {
                Storage::disk('public')->delete($siteSettings->appearance_favicon_path);
            }
            $siteSettings->appearance_favicon_path = null;
        }

        $siteSettings->update([
            'appearance_site_subtitle' => $validated['appearance_site_subtitle'],
            'appearance_default_per_page' => $validated['appearance_default_per_page'],
            'appearance_default_theme' => $validated['appearance_default_theme'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки внешнего вида сохранены.');
    }

    public function updateEmailSMTP(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_smtp_host' => ['nullable', 'string', 'max:255'],
            'email_smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email_smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,null'],
            'email_smtp_username' => ['nullable', 'string', 'max:255'],
            'email_smtp_password' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'email_smtp_host' => $validated['email_smtp_host'],
            'email_smtp_port' => $validated['email_smtp_port'],
            'email_smtp_encryption' => $validated['email_smtp_encryption'],
            'email_smtp_username' => $validated['email_smtp_username'],
            'email_smtp_password' => $validated['email_smtp_password'],
            'email_from_address' => $validated['email_from_address'],
            'email_from_name' => $validated['email_from_name'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки email сохранены.');
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_log_level' => ['required', 'string', 'in:debug,info,notice,warning,error,critical,alert,emergency'],
            'system_debug_mode' => ['boolean'],
            'system_max_execution_time' => ['required', 'integer', 'min:30', 'max:3600'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'system_log_level' => $validated['system_log_level'],
            'system_debug_mode' => $request->has('system_debug_mode'),
            'system_max_execution_time' => $validated['system_max_execution_time'],
        ]);

        // Apply log level immediately
        config(['logging.default' => 'stack']);
        // Apply max execution time
        set_time_limit($validated['system_max_execution_time']);

        return to_route('admin.settings')->with('success', 'Системные настройки сохранены.');
    }

    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $request->validate([
            'enable' => ['boolean'],
        ]);

        if ($request->boolean('enable')) {
            \Artisan::call('down', ['--retry' => 60]);

            return to_route('admin.settings')->with('success', 'Режим обслуживания включён.');
        }

        \Artisan::call('up');

        return to_route('admin.settings')->with('success', 'Режим обслуживания выключен.');
    }

    public function clearCache(Request $request): RedirectResponse
    {
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');

        return to_route('admin.settings')->with('success', 'Кэш очищен.');
    }

    public function updateSearchFilters(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'search_default_sort' => ['required', 'string', 'in:title_asc,title_desc,price_asc,price_desc,status_asc,status_desc,condition_asc,condition_desc,date_asc,date_desc,brand_asc,brand_desc,specifications_asc,specifications_desc,seller_asc,seller_desc,seller_description_asc,seller_description_desc,note_asc,note_desc,value_asc,value_desc'],
            'search_default_field' => ['required', 'string', 'in:title,seller_description'],
            'search_default_status' => ['nullable', 'string', 'in:new,watching,buy,skipped'],
            'search_price_min' => ['required', 'integer', 'min:0', 'max:999999'],
            'search_price_max' => ['required', 'integer', 'min:0', 'max:999999'],
            'search_default_condition' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->filled('search_price_max')) {
            abort_if($validated['search_price_min'] > $validated['search_price_max'], 422, 'Минимальная цена не может быть больше максимальной.');
        }

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'search_default_sort' => $validated['search_default_sort'],
            'search_default_field' => $validated['search_default_field'],
            'search_default_status' => $validated['search_default_status'] ?? null,
            'search_price_min' => $validated['search_price_min'],
            'search_price_max' => $validated['search_price_max'],
            'search_default_condition' => $validated['search_default_condition'] ?? null,
        ]);

        return to_route('admin.settings')->with('success', 'Настройки поиска и фильтров сохранены.');
    }

    public function updateCatalog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'catalog_currency' => ['required', 'string', 'in:USD,KGS'],
            'catalog_show_shipping' => ['boolean'],
            'catalog_price_format' => ['required', 'string', 'in:before,after'],
            'catalog_show_image' => ['boolean'],
            'catalog_show_seller' => ['boolean'],
            'catalog_show_condition' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'catalog_currency' => $validated['catalog_currency'],
            'catalog_show_shipping' => $request->has('catalog_show_shipping'),
            'catalog_price_format' => $validated['catalog_price_format'],
            'catalog_show_image' => $request->has('catalog_show_image'),
            'catalog_show_seller' => $request->has('catalog_show_seller'),
            'catalog_show_condition' => $request->has('catalog_show_condition'),
        ]);

        return to_route('admin.settings')->with('success', 'Настройки каталога товаров сохранены.');
    }

    public function updateUsers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_registration_enabled' => ['boolean'],
            'user_email_verification' => ['boolean'],
            'user_session_lifetime' => ['required', 'integer', 'min:30', 'max:43200'],
            'user_login_max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'user_password_min_length' => ['required', 'integer', 'min:4', 'max:32'],
            'user_welcome_email' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'user_registration_enabled' => $request->has('user_registration_enabled'),
            'user_email_verification' => $request->has('user_email_verification'),
            'user_session_lifetime' => $validated['user_session_lifetime'],
            'user_login_max_attempts' => $validated['user_login_max_attempts'],
            'user_password_min_length' => $validated['user_password_min_length'],
            'user_welcome_email' => $request->has('user_welcome_email'),
        ]);

        return to_route('admin.settings')->with('success', 'Настройки пользователей сохранены.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_log_import' => ['boolean'],
            'notify_log_errors' => ['boolean'],
            'notify_log_price_changes' => ['boolean'],
            'notify_log_new_items' => ['boolean'],
            'notify_email_reports' => ['boolean'],
            'notify_email_schedule' => ['required', 'string', 'in:daily,weekly'],
            'notify_telegram_digest' => ['boolean'],
            'notify_error_alerts' => ['boolean'],
            'notify_price_drop_alerts' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'notify_log_import' => $request->has('notify_log_import'),
            'notify_log_errors' => $request->has('notify_log_errors'),
            'notify_log_price_changes' => $request->has('notify_log_price_changes'),
            'notify_log_new_items' => $request->has('notify_log_new_items'),
            'notify_email_reports' => $request->has('notify_email_reports'),
            'notify_email_schedule' => $validated['notify_email_schedule'],
            'notify_telegram_digest' => $request->has('notify_telegram_digest'),
            'notify_error_alerts' => $request->has('notify_error_alerts'),
            'notify_price_drop_alerts' => $request->has('notify_price_drop_alerts'),
        ]);

        return to_route('admin.settings')->with('success', 'Настройки уведомлений и отчётов сохранены.');
    }

    public function updateFooter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'footer_privacy_policy' => ['nullable', 'string'],
            'footer_terms_of_service' => ['nullable', 'string'],
            'footer_contact_email' => ['nullable', 'email', 'max:255'],
            'footer_social_telegram' => ['nullable', 'string', 'url', 'max:255'],
            'footer_social_facebook' => ['nullable', 'string', 'url', 'max:255'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'footer_copyright' => $validated['footer_copyright'],
            'footer_privacy_policy' => $validated['footer_privacy_policy'],
            'footer_terms_of_service' => $validated['footer_terms_of_service'],
            'footer_contact_email' => $validated['footer_contact_email'],
            'footer_social_telegram' => $validated['footer_social_telegram'],
            'footer_social_facebook' => $validated['footer_social_facebook'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки футера сохранены.');
    }

    public function updateCaching(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cache_pages_enabled' => ['boolean'],
            'cache_pages_ttl' => ['required', 'integer', 'min:1', 'max:1440'],
            'cache_ebay_api_enabled' => ['boolean'],
            'cache_ebay_api_ttl' => ['required', 'integer', 'min:1', 'max:1440'],
            'performance_gzip' => ['boolean'],
            'performance_lazy_loading' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'cache_pages_enabled' => $request->has('cache_pages_enabled'),
            'cache_pages_ttl' => $validated['cache_pages_ttl'],
            'cache_ebay_api_enabled' => $request->has('cache_ebay_api_enabled'),
            'cache_ebay_api_ttl' => $validated['cache_ebay_api_ttl'],
            'performance_gzip' => $request->has('performance_gzip'),
            'performance_lazy_loading' => $request->has('performance_lazy_loading'),
        ]);

        return to_route('admin.settings')->with('success', 'Настройки кэширования сохранены.');
    }

    public function updateEmailNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notify_new_items' => ['boolean'],
            'email_notify_price_drops' => ['boolean'],
            'email_notify_errors' => ['boolean'],
            'email_notify_matches' => ['boolean'],
            'email_recipients' => ['nullable', 'string', 'max:1000'],
            'email_subject_template' => ['nullable', 'string', 'max:255'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'email_notify_new_items' => $request->has('email_notify_new_items'),
            'email_notify_price_drops' => $request->has('email_notify_price_drops'),
            'email_notify_errors' => $request->has('email_notify_errors'),
            'email_notify_matches' => $request->has('email_notify_matches'),
            'email_recipients' => $validated['email_recipients'] ?: null,
            'email_subject_template' => $validated['email_subject_template'] ?: null,
        ]);

        return to_route('admin.settings')->with('success', 'Настройки email-уведомлений сохранены.');
    }

    public function updateCurrencies(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'eur_per_usd' => ['required', 'numeric', 'min:0.0001', 'max:99999.9999'],
            'gbp_per_usd' => ['required', 'numeric', 'min:0.0001', 'max:99999.9999'],
            'currency_auto_update' => ['boolean'],
            'currency_update_schedule' => ['required', 'string', 'in:daily,weekly'],
            'currency_show_multi' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'eur_per_usd' => $validated['eur_per_usd'],
            'gbp_per_usd' => $validated['gbp_per_usd'],
            'currency_auto_update' => $request->has('currency_auto_update'),
            'currency_update_schedule' => $validated['currency_update_schedule'],
            'currency_show_multi' => $request->has('currency_show_multi'),
        ]);

        return to_route('admin.settings')->with('success', 'Настройки валют сохранены.');
    }

    public function updateApiAndWebhooks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_enabled' => ['boolean'],
            'webhook_url' => ['nullable', 'string', 'url', 'max:500'],
            'webhook_new_items' => ['boolean'],
            'webhook_price_drops' => ['boolean'],
            'webhook_errors' => ['boolean'],
            'webhook_secret' => ['nullable', 'string', 'max:128'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);

        // Generate API key if not set
        if (! $siteSettings->api_key) {
            $validated['api_key'] = bcrypt('api-key-'.str_random(32));
        } else {
            $validated['api_key'] = $siteSettings->api_key;
        }

        // Regenerate API key if requested
        if ($request->filled('regenerate_api_key')) {
            $validated['api_key'] = bcrypt('api-key-'.str_random(32));
        }

        // Regenerate webhook secret if requested
        if ($request->filled('regenerate_webhook_secret')) {
            $validated['webhook_secret'] = str_random(64);
        } elseif (! $siteSettings->webhook_secret) {
            $validated['webhook_secret'] = str_random(64);
        } else {
            $validated['webhook_secret'] = $siteSettings->webhook_secret;
        }

        $siteSettings->update([
            'api_key' => $validated['api_key'],
            'api_enabled' => $request->has('api_enabled'),
            'webhook_url' => $validated['webhook_url'] ?: null,
            'webhook_new_items' => $request->has('webhook_new_items'),
            'webhook_price_drops' => $request->has('webhook_price_drops'),
            'webhook_errors' => $request->has('webhook_errors'),
            'webhook_secret' => $validated['webhook_secret'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки API и вебхуков сохранены.');
    }

    public function updateLocalization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale_language' => ['required', 'string', 'in:ru,en,fr,de,es,zh'],
            'locale_date_format' => ['required', 'string', 'max:20'],
            'locale_time_format' => ['required', 'string', 'in:12h,24h'],
            'locale_number_format' => ['required', 'string', 'in:space_dot,comma_dot,dot_comma,space_comma'],
            'locale_first_day' => ['required', 'string', 'in:monday,sunday'],
            'locale_direction' => ['required', 'string', 'in:ltr,rtl'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'locale_language' => $validated['locale_language'],
            'locale_date_format' => $validated['locale_date_format'],
            'locale_time_format' => $validated['locale_time_format'],
            'locale_number_format' => $validated['locale_number_format'],
            'locale_first_day' => $validated['locale_first_day'],
            'locale_direction' => $validated['locale_direction'],
        ]);

        return to_route('admin.settings')->with('success', 'Настройки локализации сохранены.');
    }
}
