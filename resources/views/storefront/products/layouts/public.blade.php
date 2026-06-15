<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('shop.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ substr(md5_file(public_path('css/theme.css')), 0, 10) }}">
    @stack('head')
</head>
<body class="@yield('body-class')">
@hasSection('bare')
    @yield('content')
@else
    <header class="site-header">
        <div class="container">
            <a href="{{ route('home') }}" class="site-logo">{{ config('shop.name') }}<span class="dot">.</span></a>
            <span class="badge badge-brand">sprzedaż bezobsługowa</span>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container">
            <strong>{{ config('shop.name') }}</strong> — płatności obsługuje
            <strong style="color:var(--brand)">nfcpay</strong> (PayU)<br>
            <span class="small">MARCIN LULA · ul. dr Izabeli Wolfram 11, 05-800 Pruszków · NIP 8741624637 · REGON 341224327</span><br>
            <span class="small"><a href="{{ route('regulamin') }}">Regulamin sklepu</a> · kontakt: kontakt@please-support-me.com · &copy; {{ date('Y') }}</span>
        </div>
    </footer>
@endif
@stack('scripts')
</body>
</html>
