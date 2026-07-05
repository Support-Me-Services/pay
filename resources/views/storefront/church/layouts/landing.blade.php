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

    {{-- Open Graph / Twitter — podgląd linku (WhatsApp, FB, Messenger, X): samo serduszko z loga.
         ?v=hash wymusza odświeżenie cache crawlera po zmianie obrazka. --}}
    @php $ogImage = asset('img/og-supportme.png').'?v='.substr(md5_file(public_path('img/og-supportme.png')), 0, 8); @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SupportME">
    <meta property="og:title" content="@yield('title', 'SupportME — technologia, która pomaga czynić dobro')">
    <meta property="og:description" content="@yield('meta-description', 'Łączymy ludzi, wartości i nowoczesne płatności, aby wspieranie ważnych inicjatyw było prostsze niż kiedykolwiek wcześniej.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="SupportME">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'SupportME — technologia, która pomaga czynić dobro')">
    <meta name="twitter:description" content="@yield('meta-description', 'Łączymy ludzi, wartości i nowoczesne płatności, aby wspieranie ważnych inicjatyw było prostsze niż kiedykolwiek wcześniej.')">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ substr(md5_file(public_path('css/landing.css')), 0, 10) }}">
    {{-- Stopka: blok QR prowadzący na stronę główną (page-scoped, nie rusza globalnego CSS) --}}
    <style>
        .lp-footer__qr{ display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:4px; }
        .lp-footer__qr-card{ background:#fff; padding:8px; border-radius:12px; line-height:0; box-shadow:0 4px 14px rgba(0,0,0,.18); }
        .lp-footer__qr-card img{ display:block; width:108px; height:108px; }
        .lp-footer__qr-caption{ font-size:15px; line-height:1.35; color:#fff; opacity:.95; }
        .lp-footer__qr-caption strong{ display:block; font-weight:600; }
    </style>
    @stack('head')
    <style>
      .lp-footer__qr-card{cursor:zoom-in}
      .qr-zoom{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(0,0,0,.82);cursor:zoom-out}
      .qr-zoom.is-open{display:flex}
      .qr-zoom img{width:min(80vw,80vh,420px);height:auto;background:#fff;padding:18px;border-radius:14px;box-shadow:0 12px 48px rgba(0,0,0,.45)}
    </style>
</head>
<body class="lp">
<div class="lp-page">
    <header class="lp-header">
        <a href="{{ route('main') }}" aria-label="SupportME">
            <img class="lp-logo" src="{{ asset('img/landing/logo.svg') }}" alt="SupportME">
        </a>
        <nav class="lp-nav">
            <a href="{{ route('main') }}" @if(request()->routeIs('main')) aria-current="page" @endif>Strona główna</a>
            <a href="{{ route('beneficiaries') }}" @if(request()->routeIs('beneficiaries')) aria-current="page" @endif>Wspieramy</a>
            <a href="{{ route('careers') }}" @if(request()->routeIs('careers')) aria-current="page" @endif>Rekrutacja</a>
            <a href="{{ route('investors') }}" @if(request()->routeIs('investors')) aria-current="page" @endif>Inwestorzy i akcjonariusze</a>
            <a class="lp-nav__support" href="{{ route('home', ['produkt' => 'serduszko']) }}">Wesprzyj</a>
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
                <a href="{{ route('regulamin') }}">Regulamin</a>
                <a href="{{ asset('polityka-prywatnosci.pdf') }}" target="_blank" rel="noopener noreferrer">Polityka prywatności</a>
                <a href="{{ route('docs') }}">Dokumentacja</a>
                <a href="{{ route('thanks') }}">Dziękujemy</a>
            </div>
            <a class="lp-footer__social" href="https://www.linkedin.com/" target="_blank" rel="noopener" aria-label="LinkedIn">
                <img src="{{ asset('img/landing/linkedin.svg') }}" alt="LinkedIn">
            </a>
            <div class="lp-footer__legal">
                Support Me Services Marcin Lula<br>
                NIP: 8741624637<br>
                REGON: 341224327<br>
                <a href="mailto:marcin.lula@please-support-me.com" aria-label="E-mail"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="width:1em;height:1em;vertical-align:-.12em;margin-right:.45em"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>marcin.lula@please-support-me.com</a><br>
                <a href="tel:+48694841749" aria-label="Telefon"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="width:1em;height:1em;vertical-align:-.12em;margin-right:.45em"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>694 841 749</a>
            </div>
            <div class="lp-footer__qr">
                <a class="lp-footer__qr-card" href="{{ asset('img/qr-home.svg') }}" aria-label="Powiększ kod QR" onclick="event.preventDefault();document.getElementById('qrZoom').classList.add('is-open')">
                    <img src="{{ asset('img/qr-home.svg') }}" alt="Kod QR prowadzący na stronę please-support-me.com" width="108" height="108">
                </a>
                <div class="lp-footer__qr-caption">
                    <strong>Zeskanuj — wejdź na stronę</strong>
                    please-support-me.com
                </div>
            </div>
        </div>
    </footer>
</div>
    <div class="qr-zoom" id="qrZoom" role="dialog" aria-modal="true" aria-label="Powiększony kod QR" onclick="this.classList.remove('is-open')">
        <img src="{{ asset('img/qr-home.svg') }}" alt="Kod QR prowadzący na stronę please-support-me.com">
    </div>
    <script>document.addEventListener('keydown',function(e){if(e.key==='Escape'){var z=document.getElementById('qrZoom');if(z){z.classList.remove('is-open');}}});</script>
@stack('scripts')
</body>
</html>
