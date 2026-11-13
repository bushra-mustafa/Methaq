<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#203322">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icons/favicon.svg') }}">
    <link rel="icon" href="{{ asset('brand/icons/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/icons/apple-touch-icon.png') }}">
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

    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>

<body>
    @inertia
    <noscript>يرجى تفعيل JavaScript لعرض منصة ميثاق.</noscript>
</body>

</html>