@php
    $faviconSettings = \App\Models\SiteSetting::query()->first();
    $faviconPath = $faviconSettings->appearance_favicon_path ?? null;
@endphp

@if ($faviconPath)
    <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::url($faviconPath) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif
