<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('shop.name', config('app.name', 'SupportME')) }}</title>
    @vite('resources/js/app.jsx')
    @inertiaHead
</head>
<body @class(['lp' => config('platform.role') === 'storefront'])>
    @inertia
</body>
</html>
