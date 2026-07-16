<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <title inertia>{{ config('shop.name', config('app.name', 'SupportME')) }}</title>
    @vite('resources/js/app.jsx')
    @inertiaHead
</head>
<body @class(['lp' => config('platform.role') === 'storefront'])>
    @inertia
</body>
</html>
