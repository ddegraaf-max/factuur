<!DOCTYPE html>
<html lang="{{ market('locale') }}" data-brand="{{ \App\Support\Brand::key() }}" data-brand-name="{{ brand('name') }}" data-brand-color="{{ brand('color') }}" data-locale="{{ market('locale') }}" data-currency="{{ market('currency') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Nette SEO-titels voor de twee openbare app-pagina's; de app zelf
        // (achter login) hoeft niet gevonden te worden. Teksten per merk in config/brand.php.
        $seoPages = [
            'login' => [
                'title' => brand('login_seo_title'),
                'description' => brand('login_seo_description'),
            ],
            'register' => [
                'title' => brand('register_seo_title'),
                'description' => brand('register_seo_description'),
            ],
        ];
        $seo = $seoPages[request()->path()] ?? null;
    @endphp
    <title inertia>{{ $seo['title'] ?? brand('name') }}</title>
    @if($seo)
        <meta name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ rtrim(config('app.url'), '/') . request()->getPathInfo() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ brand('name') }}">
        <meta property="og:locale" content="nl_NL">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:image" content="{{ \App\Support\Brand::asset('og_image') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ \App\Support\Brand::asset('og_image') }}">
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seo['title'],
            'description' => $seo['description'],
            'url' => url()->current(),
            'inLanguage' => 'nl',
            'isPartOf' => ['@type' => 'WebSite', 'name' => brand('name'), 'url' => url('/')],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    @if(brand('favicon_svg'))
    <link rel="icon" type="image/svg+xml" href="{{ brand('favicon_svg') }}">
    @endif
    <link rel="icon" type="image/png" sizes="32x32" href="{{ brand('favicon_32') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ brand('favicon_512') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ brand('apple_touch') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ brand('color') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ brand('name') }}">

    @if(brand('fonts_url'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ brand('fonts_url') }}" rel="stylesheet">
    @endif
    @if(brand('theme_css'))
    {{-- Merkkleuren bovenop de standaard-tokens; html[data-brand] wint van :root. --}}
    <link rel="stylesheet" href="{{ brand('theme_css') }}?v={{ \App\Support\Brand::versionNumber() }}">
    @endif

    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
