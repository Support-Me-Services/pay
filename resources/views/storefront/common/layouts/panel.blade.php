<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel sklepu') — {{ config('shop.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ substr(md5_file(public_path('css/theme.css')), 0, 10) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @stack('head')
</head>
<body>
<div class="panel-wrap">
    <aside class="panel-sidebar">
        <div class="brand">{{ config('shop.name') }}<span class="dot">.</span></div>
        @php
            $unreadMessages = \App\Modules\Storefront\Models\ContactMessage::where('is_read', false)->count();
            $unreadApplications = \App\Modules\Storefront\Models\JobApplication::where('is_read', false)->count();
        @endphp
        <nav class="panel-nav">
            <a href="{{ route('panel.dashboard') }}" class="{{ request()->routeIs('panel.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('panel.products.index') }}" class="{{ request()->routeIs('panel.products.*') || request()->routeIs('panel.parishes.*') ? 'active' : '' }}">Parafie</a>
            <a href="{{ route('panel.categories.index') }}" class="{{ request()->routeIs('panel.categories.*') ? 'active' : '' }}">Kategorie</a>
            <a href="{{ route('panel.salespeople.index') }}" class="{{ request()->routeIs('panel.salespeople.*') ? 'active' : '' }}">Handlowcy</a>
            <a href="{{ route('panel.potential-parishes.index') }}" class="{{ request()->routeIs('panel.potential-parishes.*') ? 'active' : '' }}">Parafie do obdzwonienia</a>
            <a href="{{ route('panel.coverage.map') }}" class="{{ request()->routeIs('panel.coverage.*') ? 'active' : '' }}">Mapa pokrycia</a>
            <a href="{{ route('panel.positions.index') }}" class="{{ request()->routeIs('panel.positions.*') ? 'active' : '' }}">Praca</a>
            <a href="{{ route('panel.applications.index') }}" class="{{ request()->routeIs('panel.applications.*') ? 'active' : '' }}">
                Aplikacje
                @if($unreadApplications > 0)
                    <span class="badge badge-brand" style="margin-left:6px">{{ $unreadApplications }}</span>
                @endif
            </a>
            <a href="{{ route('panel.messages.index') }}" class="{{ request()->routeIs('panel.messages.*') ? 'active' : '' }}">
                Wiadomości
                @if($unreadMessages > 0)
                    <span class="badge badge-brand" style="margin-left:6px">{{ $unreadMessages }}</span>
                @endif
            </a>
            <a href="{{ route('home') }}" target="_blank">Podgląd sklepu ↗</a>
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
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
