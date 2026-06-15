<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NFC Pay — sprzedawaj bez kasy i bez obsługi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">
    @stack('head')
</head>
<body>
@hasSection('bare')
    @yield('content')
@else
    <header class="site-header">
        <div class="container">
            <a href="{{ route('landing') }}" class="site-logo">nfc<span class="dot">pay</span></a>
            <nav class="site-nav">
                <a href="/#wdrozenia">Wdrożenia</a>
                <a href="/#bezpieczenstwo">Bezpieczeństwo</a>
                <a href="/#cennik">Cennik</a>
                <a href="/#faq">FAQ</a>
            </nav>
            <a href="#kontakt" class="btn btn-primary btn-sm">Zostaw kontakt</a>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container">
            <strong>nfc<span style="color:var(--brand)">pay</span></strong> — sprzedaż bezobsługowa NFC · demo MVP<br>
            <span class="small">&copy; {{ date('Y') }} please-support-me.com</span>
        </div>
    </footer>
@endif
@stack('scripts')
</body>
</html>
