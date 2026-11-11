<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#074B36">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icons/favicon.svg') }}">
    <link rel="icon" href="{{ asset('brand/icons/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/icons/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Cairo:wght@400;500;600;700&family=EB+Garamond:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @viteReactRefresh
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>

<body>
    @inertia
    <noscript>يرجى تفعيل JavaScript لعرض منصة ميثاق.</noscript>
</body>

</html>