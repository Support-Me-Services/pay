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
            <a href="{{ route('main') }}" @if(request()->routeIs('main')) aria-current="page" @endif>Strona główna</a>
            <a class="lp-nav__shop" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Sklep</a>
            <a href="{{ route('careers') }}" @if(request()->routeIs('careers')) aria-current="page" @endif>Rekrutacja</a>
            <a href="{{ route('investors') }}" @if(request()->routeIs('investors')) aria-current="page" @endif>Inwestorzy i akcjonariusze</a>
            <a href="{{ route('cart') }}" class="lp-nav__cart" aria-label="Koszyk">Koszyk{!! $lpCartCount > 0 ? '<span class="lp-cart-badge">'.$lpCartCount.'</span>' : '' !!}</a>
            <button type="button" class="lp-nav__support" data-wesprzyj-open>Wesprzyj</button>
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
                <a href="{{ route('investors') }}">Inwestorzy i akcjonariusze</a>
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

{{-- Modal „WESPRZYJ" (szybkie wsparcie 10 zł) — wg Figmy SKLEP (65:1616) --}}
<div class="wesprzyj" id="wesprzyj" hidden>
    <div class="wesprzyj__scrim" data-wesprzyj-close></div>
    <div class="wesprzyj__dialog" role="dialog" aria-modal="true" aria-label="Wesprzyj SupportMe">
        <button type="button" class="wesprzyj__close" data-wesprzyj-close aria-label="Zamknij">&times;</button>
        <div class="wesprzyj__pill">10zł</div>
        <img class="wesprzyj__heart" src="{{ asset('img/sklep/heart-wesprzyj.svg') }}" alt="" aria-hidden="true">
        <form method="POST" action="{{ route('support.pay') }}" class="wesprzyj__form">
            @csrf
            <button type="submit" class="wesprzyj__btn">WESPRZYJ</button>
        </form>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('wesprzyj');
    if (!modal) return;
    function open() { modal.hidden = false; document.body.style.overflow = 'hidden'; }
    function close() { modal.hidden = true; document.body.style.overflow = ''; }
    document.querySelectorAll('[data-wesprzyj-open]').forEach(function (b) { b.addEventListener('click', open); });
    modal.querySelectorAll('[data-wesprzyj-close]').forEach(function (b) { b.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
@stack('scripts')
</body>
</html>
