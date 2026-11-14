<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#203322">
    @php($iconVersion = '?v='.filemtime(public_path('brand/icons/favicon.svg')))
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icons/favicon.svg').$iconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/icons/favicon-32.png').$iconVersion }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('brand/icons/favicon-16.png').$iconVersion }}">
    <link rel="icon" href="{{ asset('brand/icons/favicon.ico').$iconVersion }}" sizes="any">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/icons/apple-touch-icon.png').$iconVersion }}">
    @php($meta = $page['props']['meta'] ?? null)
    <title inertia>{{ $meta['title'] ?? 'ميثاق' }}</title>
    @if ($meta)
        <meta name="description" content="{{ $meta['description'] }}">
        <link rel="canonical" href="{{ $meta['url'] }}">
        @if ($meta['noindex'])
            <meta name="robots" content="noindex, nofollow">
        @endif
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ميثاق">
        <meta property="og:locale" content="ar_AR">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ $meta['url'] }}">
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="{{ $meta['image'] }}">
        @else
            <meta name="twitter:card" content="summary">
        @endif
    @endif

    <link rel="preload" href="/brand/fonts/NotoSansArabic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/brand/fonts/Amiri-Bold.woff2" as="font" type="font/woff2" crossorigin>

    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>

<body>
    @inertia
    <div id="boot-loader" role="status" aria-label="جارٍ التحميل"><img src="/brand/logos/methaq-symbol-small-flat.svg" alt="" width="64" height="64"></div>
    <noscript>يرجى تفعيل JavaScript لعرض منصة ميثاق.</noscript>
</body>

</html>