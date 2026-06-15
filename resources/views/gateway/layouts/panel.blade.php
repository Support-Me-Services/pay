<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel bramki') — NFC Pay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @stack('head')
</head>
<body>
<div class="panel-wrap">
    <aside class="panel-sidebar">
        <div class="brand">nfc<span class="dot">pay</span> panel</div>
        <nav class="panel-nav">
            <a href="{{ route('panel.dashboard') }}" class="{{ request()->routeIs('panel.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('panel.shops.index') }}" class="{{ request()->routeIs('panel.shops.*') || request()->routeIs('panel.tags.*') ? 'active' : '' }}">Sklepy</a>
            <a href="{{ route('panel.stats') }}" class="{{ request()->routeIs('panel.stats') ? 'active' : '' }}">Statystyki</a>
            <a href="{{ route('panel.leads') }}" class="{{ request()->routeIs('panel.leads') ? 'active' : '' }}">Leady</a>
            <a href="{{ route('panel.antitheft') }}" class="{{ request()->routeIs('panel.antitheft') ? 'active' : '' }}">AntiTheft</a>
            <div class="nav-sep"></div>
            <form method="POST" action="{{ route('panel.logout') }}">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;">Wyloguj</a>
            </form>
        </nav>
    </aside>
    <main class="panel-main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
