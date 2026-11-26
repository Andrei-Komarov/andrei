@php
    $siteSettings = \App\Models\SiteSetting::query()->first();
    $defaultTheme = $siteSettings->appearance_default_theme ?? 'light';
    $faviconPath = $siteSettings->appearance_favicon_path ?? null;
@endphp

<script>
    // Critical: apply theme BEFORE any rendering to prevent flash
    (function() {
        try {
            var saved = localStorage.getItem('laptop-finder-theme');
            var theme = saved ? (saved === 'light' ? 'light' : 'dark') : '{{ $defaultTheme }}';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#0f172a';
                document.body.style.backgroundColor = '#0f172a';
            } else {
                document.documentElement.style.backgroundColor = '#f1f5f9';
                document.body.style.backgroundColor = '#f1f5f9';
            }
            document.documentElement.dataset.theme = theme;
        } catch (e) {
            document.documentElement.classList.toggle('dark', '{{ $defaultTheme }}' === 'dark');
        }
    })();
</script>

@if ($faviconPath)
    <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::url($faviconPath) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif

<style>
    html {
        background-color: #f1f5f9;
        color-scheme: light;
    }

    html.dark,
    html.dark body {
        background-color: #0f172a;
        color-scheme: dark;
    }
</style>
