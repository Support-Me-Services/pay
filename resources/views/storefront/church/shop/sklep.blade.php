@extends('layouts.landing')

@section('title', 'Sklep firmowy — ' . config('shop.name'))
@section('meta-description', 'Gadżety charytatywne SupportMe — kubki, koszulki, piny i breloki. Każdy zakup wspiera rozwój aplikacji i infrastruktury fundacji.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/sklep.css') }}?v={{ substr(md5_file(public_path('css/sklep.css')), 0, 10) }}">
@endpush

@section('content')
    {{-- Pasek hero „Sklep firmowy" (gradient wg Figmy 134.5°) --}}
    <section class="shop-hero">
        <h1>Sklep firmowy</h1>
    </section>

    <div class="shop-wrap">
        @if(session('success'))
            <div class="shop-flash">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="shop-flash shop-flash--err">{{ session('error') }}</div>
        @endif

        <p class="shop-intro">
            Wybierając nasze gadżety charytatywne, takie jak koszulki czy kubki, bezpośrednio wspierasz rozwój
            technologiczny fundacji i aplikacji SupportMe. Każdy z tych produktów to realna cegiełka, która finansuje
            utrzymanie naszej infrastruktury IT oraz pozwala tworzyć bezpieczne, innowacyjne narzędzia do cyfrowych
            zbiórek. Wybierz swój fizyczny symbol wsparcia i pomóż nam na co dzień promować ideę nowoczesnego pomagania
            w swoim otoczeniu!
        </p>

        <div class="shop-grid">
            @foreach($products as $p)
                <div class="shop-card">
                    <div class="shop-card__art">
                        <img src="{{ asset($p['image']) }}" alt="{{ $p['name'] }}" loading="lazy">
                    </div>
                    <div class="shop-card__body">
                        <div class="shop-card__name">{{ $p['name'] }}</div>
                        <div class="shop-card__price">{{ number_format($p['price'] / 100, 0, ',', ' ') }} zł</div>
                        <form method="POST" action="{{ route('cart.add', $p['slug']) }}" class="shop-card__form" data-add>
                            @csrf
                            <button type="submit" class="shop-card__btn">Dodaj do koszyka</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Toast „dodano do koszyka" (AJAX) --}}
    <div class="shop-toast" id="shopToast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
<script>
(function () {
    var toast = document.getElementById('shopToast');
    var badge = document.querySelector('.lp-cart-badge');
    var cartLink = document.querySelector('.lp-nav__cart');
    var t;
    function showToast(msg) {
        toast.textContent = msg;
        toast.hidden = false;
        toast.classList.add('is-on');
        clearTimeout(t);
        t = setTimeout(function () { toast.classList.remove('is-on'); }, 2200);
    }
    function setBadge(count) {
        if (!cartLink) return;
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'lp-cart-badge';
            cartLink.appendChild(badge);
        }
        badge.textContent = count;
    }
    document.querySelectorAll('form[data-add]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
                           'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value },
                body: new FormData(form),
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d && d.ok) { setBadge(d.count); showToast('Dodano do koszyka: ' + d.name); }
            }).catch(function () { form.submit(); });   // fallback bez JS/AJAX
        });
    });
})();
</script>
@endpush
