<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1473C0">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', 'SupportME — technologia, która pomaga czynić dobro')</title>
    <meta name="description" content="@yield('meta-description', 'Łączymy ludzi, wartości i nowoczesne płatności, aby wspieranie ważnych inicjatyw było prostsze niż kiedykolwiek wcześniej.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ substr(md5_file(public_path('css/landing.css')), 0, 10) }}">
    @stack('head')
</head>
<body class="lp">
<div class="lp-page">
    <header class="lp-header">
        <a href="{{ route('home') }}" aria-label="SupportME">
            <img class="lp-logo" src="{{ asset('img/landing/logo.svg') }}" alt="SupportME">
        </a>
        @php $lpCartCount = (int) collect(session('merch_cart', []))->sum(); @endphp
        <nav class="lp-nav">
            <a href="{{ route('main') }}">Strona główna</a>
            <a class="lp-nav__shop" href="{{ route('home') }}">Sklep</a>
            <a href="{{ route('careers') }}">Rekrutacja</a>
            <a href="{{ route('regulamin') }}">Inwestorzy i akcjonariusze</a>
            <a href="{{ route('cart') }}" class="lp-nav__cart" aria-label="Koszyk">Koszyk{!! $lpCartCount > 0 ? '<span class="lp-cart-badge">'.$lpCartCount.'</span>' : '' !!}</a>
        </nav>
        <button class="lp-burger" type="button" aria-label="Menu" aria-expanded="false"
                onclick="var h=this.closest('.lp-header');var o=h.classList.toggle('lp-open');this.setAttribute('aria-expanded',o)">
            <span></span><span></span><span></span>
        </button>
    </header>

    @yield('content')

    <footer class="lp-footer">
        <div class="lp-footer__inner">
            <img class="lp-footer__logo" src="{{ asset('img/landing/logo-footer.svg') }}" alt="SupportME">
            <div class="lp-footer__links">
                <a href="{{ route('careers') }}">Rekrutacja</a>
                <a href="{{ route('regulamin') }}">Inwestorzy i akcjonariusze</a>
                <a href="{{ route('regulamin') }}">Polityka prywatności i regulamin</a>
            </div>
            <a class="lp-footer__social" href="https://www.linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn">
                <img src="{{ asset('img/landing/linkedin.svg') }}" alt="LinkedIn">
            </a>
            <div class="lp-footer__legal">
                MLI - Marcin Lula Informatyka<br>
                NIP: 8741624637
            </div>
        </div>
    </footer>
</div>
@stack('scripts')
</body>
</html>
