<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSEOController extends Controller
{
    public function seo(): View
    {
        return view('admin.seo.seo', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateSEO(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seo_site_name' => ['required', 'string', 'max:255'],
            'seo_site_description' => ['required', 'string', 'max:255'],
            'seo_keywords' => ['required', 'string', 'max:255'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'seo_site_name' => $validated['seo_site_name'],
            'seo_site_description' => $validated['seo_site_description'],
            'seo_keywords' => $validated['seo_keywords'],
        ]);

        return to_route('admin.seo.seo')->with('success', 'SEO-настройки сохранены.');
    }

    public function advancedSEO(): View
    {
        return view('admin.seo.advanced', [
            'siteSettings' => SiteSetting::query()->findOrFail(1),
        ]);
    }

    public function updateAdvancedSEO(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seo_canonical_url' => ['nullable', 'string', 'url', 'max:2048'],
            'seo_og_title' => ['nullable', 'string', 'max:255'],
            'seo_og_description' => ['nullable', 'string', 'max:255'],
            'seo_og_image' => ['nullable', 'string', 'url', 'max:2048'],
            'seo_og_url' => ['nullable', 'string', 'url', 'max:2048'],
            'seo_twitter_title' => ['nullable', 'string', 'max:255'],
            'seo_twitter_description' => ['nullable', 'string', 'max:255'],
            'seo_twitter_image' => ['nullable', 'string', 'url', 'max:2048'],
        ]);

        $siteSettings = SiteSetting::query()->findOrFail(1);
        $siteSettings->update([
            'seo_canonical_url' => $validated['seo_canonical_url'],
            'seo_og_title' => $validated['seo_og_title'],
            'seo_og_description' => $validated['seo_og_description'],
            'seo_og_image' => $validated['seo_og_image'],
            'seo_og_url' => $validated['seo_og_url'],
            'seo_twitter_title' => $validated['seo_twitter_title'],
            'seo_twitter_description' => $validated['seo_twitter_description'],
            'seo_twitter_image' => $validated['seo_twitter_image'],
        ]);

        return to_route('admin.seo.advanced')->with('success', 'Расширенные SEO-настройки сохранены.');
    }
}
