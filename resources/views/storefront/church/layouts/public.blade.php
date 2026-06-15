<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#141d33">
    <title>@yield('title', config('shop.name'))</title>
    <meta name="description" content="@yield('meta-description', 'Cyfrowa taca — wesprzyj swój kościół jednym dotknięciem telefonu. Szybko, bezpiecznie, bez gotówki.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/church.css') }}?v={{ filemtime(public_path('css/church.css')) }}">
    @stack('head')
</head>
<body class="@yield('body-class')">
@hasSection('bare')
    @yield('content')
@else
    <header class="site-header">
        <div class="bar">
            <a href="{{ route('home') }}" class="wordmark">
                <span class="plate" aria-hidden="true"></span>{{ config('shop.name') }}
            </a>
            <span class="header-note">cyfrowa taca parafialna</span>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container">
            <strong>{{ config('shop.name') }}</strong> — wpłaty obsługuje
            <span class="brand">nfcpay</span> (PayU).<br>
            Twoje wsparcie trafia w całości do wybranej parafii.
            <div class="fine">
                Operator płatności: MICHAŁ ŻOŁĄDKOWICZ SULI · ul. Jana Kilińskiego 13/36, 19-300 Ełk · NIP 8481749996<br>
                <a href="{{ route('regulamin') }}">Regulamin</a> · kontakt: michal@suli.pl · tel. 691 102 010 · &copy; {{ date('Y') }}
            </div>
        </div>
    </footer>
@endif
@stack('scripts')
</body>
</html>
