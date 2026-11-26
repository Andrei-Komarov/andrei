@props(['title' => null, 'description' => null, 'keywords' => null])

@php
    $siteName = $title ?? config('app.name', 'Finder');
    $seoSettings = \App\Models\SiteSetting::query()->first();

    $siteName = $seoSettings->seo_site_name ?? 'Finder';
    $seoDescription = $description ?? ($seoSettings->seo_site_description ?? 'Поиск товаров на eBay');
    $seoKeywords = $keywords ?? ($seoSettings->seo_keywords ?? 'eBay, товары, поиск, ноутбуки');

    // Advanced SEO
    $canonicalUrl = $seoSettings->seo_canonical_url ?? null;
    $ogTitle = $seoSettings->seo_og_title ?? $siteName;
    $ogDescription = $seoSettings->seo_og_description ?? $seoDescription;
    $ogImage = $seoSettings->seo_og_image ?? null;
    $ogUrl = $seoSettings->seo_og_url ?? $canonicalUrl ?? null;

    $twitterTitle = $seoSettings->seo_twitter_title ?? $ogTitle;
    $twitterDescription = $seoSettings->seo_twitter_description ?? $ogDescription;
    $twitterImage = $seoSettings->seo_twitter_image ?? $ogImage;
@endphp

<title>{{ $siteName }} — {{ config('app.name', 'Finder') }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="keywords" content="{{ $seoKeywords }}">

@if ($canonicalUrl)
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif

<!-- Open Graph -->
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif
@if ($ogUrl)
    <meta property="og:url" content="{{ $ogUrl }}">
@endif
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $twitterTitle }}">
<meta name="twitter:description" content="{{ $twitterDescription }}">
@if ($twitterImage)
    <meta name="twitter:image" content="{{ $twitterImage }}">
@endif
