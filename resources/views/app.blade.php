<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Nette SEO-titels voor de twee openbare app-pagina's; de app zelf
        // (achter login) hoeft niet gevonden te worden.
        $seoPages = [
            'login' => [
                'title' => 'Inloggen bij EasyInvoice — online factuurprogramma',
                'description' => 'Log in bij EasyInvoice en ga verder met je administratie: facturen, offertes, BTW en incasso — Nederlandse facturatie zonder gedoe.',
            ],
            'register' => [
                'title' => 'Probeer EasyInvoice 14 dagen gratis — account aanmaken',
                'description' => 'Maak in één minuut je EasyInvoice-account aan en probeer alle functies 14 dagen gratis — geen creditcard nodig, daarna vanaf € 12,10 per maand.',
            ],
        ];
        $seo = $seoPages[request()->path()] ?? null;
    @endphp
    <title inertia>{{ $seo['title'] ?? config('app.name', 'EasyInvoice') }}</title>
    @if($seo)
        <meta name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="EasyInvoice">
        <meta property="og:locale" content="nl_NL">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:image" content="{{ url('/images/og-easyinvoice.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ url('/images/og-easyinvoice.png') }}">
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seo['title'],
            'description' => $seo['description'],
            'url' => url()->current(),
            'inLanguage' => 'nl',
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'EasyInvoice', 'url' => url('/')],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    <link rel="icon" type="image/png" sizes="32x32" href="/images/easyinvoice-favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/easyinvoice-favicon-512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/easyinvoice-favicon-180.png">

    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
