<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#074B36">
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icons/favicon.svg') }}">
        <link rel="icon" href="{{ asset('brand/icons/favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('brand/icons/apple-touch-icon.png') }}">
        @viteReactRefresh
        @vite('resources/js/app.tsx')
        @inertiaHead
    </head>
    <body>
        @inertia
        <noscript>يرجى تفعيل JavaScript لعرض منصة ميثاق.</noscript>
    </body>
</html>
