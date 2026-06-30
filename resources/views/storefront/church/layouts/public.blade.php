<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#141d33">
    <title>@yield('title', config('shop.name'))</title>
    <meta name="description" content="@yield('meta-description', 'Cyfrowa taca — wesprzyj swój kościół jednym dotknięciem telefonu. Szybko, bezpiecznie, bez gotówki.')">

    {{-- Open Graph / Twitter — podgląd linku (WhatsApp itp.): samo serduszko z loga --}}
    @php $ogImage = asset('img/og-supportme.png').'?v='.substr(md5_file(public_path('img/og-supportme.png')), 0, 8); @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SupportME">
    <meta property="og:title" content="@yield('title', config('shop.name'))">
    <meta property="og:description" content="@yield('meta-description', 'Cyfrowa taca — wesprzyj swój kościół jednym dotknięciem telefonu. Szybko, bezpiecznie, bez gotówki.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="SupportME">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/church.css') }}?v={{ substr(md5_file(public_path('css/church.css')), 0, 10) }}">
    <style>
        .header-nav { display: flex; gap: 18px; }
        .header-nav a { color: #f6f1e6; opacity: .85; font-size: .92rem; font-weight: 500; text-decoration: none; }
        .header-nav a:hover { opacity: 1; color: #e2bf6a; }
    </style>
    @stack('head')
</head>
<body class="@yield('body-class')">
@hasSection('bare')
    @yield('content')
@else
    <header class="site-header">
        <div class="bar">
            <a href="{{ route('main') }}" class="wordmark">
                <span class="plate" aria-hidden="true"></span>{{ config('shop.name') }}
            </a>
            <nav class="header-nav">
                <a href="{{ route('careers') }}">Praca</a>
                <a href="{{ route('contact.show') }}">Kontakt</a>
            </nav>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container">
            <strong>{{ config('shop.name') }}</strong> — wpłaty obsługuje
            <span class="brand">PayU</span>.<br>
            Twoje wsparcie trafia w całości do wybranej parafii.
            <div class="fine">
                Operator płatności: MARCIN LULA · ul. dr Izabeli Wolfram 11, 05-800 Pruszków · NIP 8741624637<br>
                <a href="{{ route('careers') }}">Praca</a> · <a href="{{ route('contact.show') }}">Kontakt</a> · <a href="{{ route('regulamin') }}">Regulamin</a> · kontakt: kontakt@please-support-me.com · &copy; {{ date('Y') }}
            </div>
        </div>
    </footer>
@endif
@stack('scripts')
</body>
</html>
