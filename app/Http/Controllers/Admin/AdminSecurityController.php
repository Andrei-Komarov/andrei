<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSecurityController extends Controller
{
    public function security(): View
    {
        return view('admin.security.security', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'security_2fa_enabled' => ['boolean'],
            'security_admin_ip_whitelist' => ['nullable', 'string', 'max:1000'],
            'security_admin_session_lifetime' => ['required', 'integer', 'min:15', 'max:10080'],
            'security_admin_max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'security_log_admin_logins' => ['boolean'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'security_2fa_enabled' => $request->has('security_2fa_enabled'),
            'security_admin_ip_whitelist' => $validated['security_admin_ip_whitelist'] ?: null,
            'security_admin_session_lifetime' => $validated['security_admin_session_lifetime'],
            'security_admin_max_attempts' => $validated['security_admin_max_attempts'],
            'security_log_admin_logins' => $request->has('security_log_admin_logins'),
        ]);

        return to_route('admin.security.security')->with('success', 'Настройки безопасности сохранены.');
    }

    public function cors(): View
    {
        return view('admin.security.cors', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateCors(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cors_allowed_origins' => ['required', 'string', 'max:1000'],
            'cors_allowed_methods' => ['required', 'string', 'max:200'],
            'cors_allowed_headers' => ['required', 'string', 'max:500'],
            'cors_allow_credentials' => ['boolean'],
            'api_rate_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'api_rate_limit_window' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'cors_allowed_origins' => $validated['cors_allowed_origins'],
            'cors_allowed_methods' => $validated['cors_allowed_methods'],
            'cors_allowed_headers' => $validated['cors_allowed_headers'],
            'cors_allow_credentials' => $request->has('cors_allow_credentials'),
            'api_rate_limit' => $validated['api_rate_limit'],
            'api_rate_limit_window' => $validated['api_rate_limit_window'],
        ]);

        return to_route('admin.security.cors')->with('success', 'Настройки CORS и безопасности API сохранены.');
    }

    public function advanced(): View
    {
        return view('admin.security.advanced', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateAdvanced(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'security_ip_blacklist' => ['nullable', 'string', 'max:2000'],
            'security_ip_whitelist' => ['nullable', 'string', 'max:2000'],
            'security_ip_whitelist_enabled' => ['boolean'],
            'security_blocked_user_agents' => ['nullable', 'string', 'max:1000'],
            'security_honeypot_enabled' => ['boolean'],
            'security_max_upload_size' => ['required', 'integer', 'min:1', 'max:1024'],
            'security_allowed_file_types' => ['required', 'string', 'max:200'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'security_ip_blacklist' => $validated['security_ip_blacklist'] ?: null,
            'security_ip_whitelist' => $validated['security_ip_whitelist'] ?: null,
            'security_ip_whitelist_enabled' => $request->has('security_ip_whitelist_enabled'),
            'security_blocked_user_agents' => $validated['security_blocked_user_agents'] ?: null,
            'security_honeypot_enabled' => $request->has('security_honeypot_enabled'),
            'security_max_upload_size' => $validated['security_max_upload_size'],
            'security_allowed_file_types' => $validated['security_allowed_file_types'],
        ]);

        return to_route('admin.security.advanced')->with('success', 'Настройки расширенной безопасности сохранены.');
    }
}
