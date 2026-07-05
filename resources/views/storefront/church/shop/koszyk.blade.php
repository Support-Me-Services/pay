@extends('layouts.landing')

@section('title', 'Koszyk — ' . config('shop.name'))
@section('meta-description', 'Twój koszyk w sklepie SupportMe.')

@push('head')
<style>
    .cart{ max-width:820px; margin:0 auto; padding:32px 20px 64px; }
    .cart h1{ font-size:30px; margin:0 0 20px; }
    .cart__flash{ padding:12px 16px; border-radius:10px; font-size:15px; margin-bottom:18px; }
    .cart__flash.is-ok{ background:#e7f7ee; color:#1a7f45; border:1px solid #b7e4c7; }
    .cart__flash.is-err{ background:#fdeaea; color:#b02525; border:1px solid #f5c2c2; }
    .cart__empty{ color:#4a5568; font-size:17px; margin:8px 0 20px; }
    .citem{ display:grid; grid-template-columns:64px 1fr auto auto auto; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid #eef1f4; }
    .citem__img{ width:64px; height:64px; object-fit:contain; background:#f6f9fb; border-radius:10px; }
    .citem__name{ font-weight:700; }
    .citem__unit{ font-size:13px; color:#6b7280; }
    .citem__qty{ display:flex; gap:6px; align-items:center; }
    .citem__qty input{ width:60px; padding:7px 8px; border:1px solid #d5dbe2; border-radius:8px; text-align:center; }
    .citem__set{ padding:7px 10px; border:1px solid #d5dbe2; background:#fff; border-radius:8px; cursor:pointer; font-size:13px; }
    .citem__line{ font-weight:800; min-width:90px; text-align:right; }
    .citem__del{ width:30px; height:30px; border:0; border-radius:50%; background:#f1f3f6; color:#8a94a2; font-size:18px; line-height:1; cursor:pointer; }
    .citem__del:hover{ background:#fdeaea; color:#b02525; }
    .cart__foot{ margin-top:24px; }
    .cart__total{ text-align:right; font-size:20px; margin-bottom:16px; }
    .cart__total strong{ font-size:26px; }
    .cart__actions{ display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .cart__back{ color:#2563eb; text-decoration:none; font-weight:600; }
    .cart__buy{ padding:13px 26px; border:0; border-radius:12px; background:#2563eb; color:#fff; font-weight:800; font-size:16px; cursor:pointer; }
    .cart__buy:hover{ background:#1d4ed8; }
    .cart__ship{ color:#6b7280; font-size:13.5px; margin:16px 0 4px; }
    .cart__policy{ color:#6b7280; font-size:12.5px; margin:0; }
    @media (max-width:560px){ .citem{ grid-template-columns:52px 1fr auto; row-gap:8px; } .citem__line{ grid-column:2/4; text-align:left; } }
    .ship{ margin:24px 0 6px; }
    .ship__title{ font-weight:700; font-size:17px; margin-bottom:10px; }
    .ship__opt{ display:flex; align-items:center; gap:10px; padding:11px 13px; border:1px solid #e6eaef; border-radius:10px; margin-bottom:8px; cursor:pointer; }
    .ship__opt:has(input:checked){ border-color:#2563eb; background:#f5f8ff; }
    .ship__label{ flex:1; }
    .ship__price{ font-weight:700; color:#334155; }
    .ship__point{ margin:2px 0 10px; }
    .ship__point input{ width:100%; padding:10px 12px; border:1px solid #d5dbe2; border-radius:8px; }
    .ship__apply{ padding:9px 15px; border:1px solid #2563eb; background:#fff; color:#2563eb; border-radius:8px; font-weight:700; cursor:pointer; }
    .cart__sums{ margin:18px 0; }
    .cart__row{ display:flex; justify-content:space-between; padding:5px 0; color:#334155; }
    .cart__row--total{ border-top:1px solid #e6eaef; margin-top:6px; padding-top:12px; font-size:20px; }
    .cart__row--total strong{ font-size:24px; }
</style>
@endpush

@section('content')
<main class="cart">
    <h1>Twój koszyk</h1>

    @if(session('success'))<div class="cart__flash is-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="cart__flash is-err">{{ session('error') }}</div>@endif

    @if($lines->isEmpty())
        <p class="cart__empty">Koszyk jest pusty.</p>
        <a class="cart__back" href="{{ route('home') }}">← Wróć do sklepu</a>
    @else
        <div class="cart__list">
            @foreach($lines as $l)
                <div class="citem">
                    <img class="citem__img" src="{{ asset($l['item']->image) }}" alt="{{ $l['item']->name }}">
                    <div class="citem__main">
                        <div class="citem__name">{{ $l['item']->name }}</div>
                        <div class="citem__unit">{{ $l['item']->pricePln() }} zł / szt.</div>
                    </div>
                    <form class="citem__qty" method="POST" action="{{ route('cart.update', $l['item']->slug) }}">
                        @csrf
                        <input type="number" name="qty" min="0" max="99" value="{{ $l['qty'] }}" aria-label="Ilość">
                        <button type="submit" class="citem__set">Zmień</button>
                    </form>
                    <div class="citem__line">{{ number_format($l['lineGrosze'] / 100, 2, ',', ' ') }} zł</div>
                    <form method="POST" action="{{ route('cart.remove', $l['item']->slug) }}">
                        @csrf
                        <button type="submit" class="citem__del" aria-label="Usuń z koszyka">&times;</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="cart__foot">
            <div class="ship">
                <div class="ship__title">Dostawa</div>
                <form method="POST" action="{{ route('cart.shipping') }}">
                    @csrf
                    @foreach($methods as $code => $m)
                        <label class="ship__opt">
                            <input type="radio" name="ship" value="{{ $code }}" @checked($code === $shipCode)
                                   data-point="{{ $m['point'] ? 1 : 0 }}" onchange="shipToggle(this)">
                            <span class="ship__label">{{ $m['label'] }}</span>
                            <span class="ship__price">{{ $m['price'] ? number_format($m['price'] / 100, 2, ',', ' ').' zł' : 'gratis' }}</span>
                        </label>
                    @endforeach
                    <div class="ship__point" id="shipPoint" @style(['display:none' => ! $methods[$shipCode]['point']])>
                        <input type="text" name="ship_point" value="{{ $shipPoint }}" maxlength="64"
                               placeholder="Nr paczkomatu / punktu odbioru (np. WAW01A)">
                    </div>
                    <button type="submit" class="ship__apply">Zastosuj dostawę</button>
                </form>
            </div>

            <div class="cart__sums">
                <div class="cart__row"><span>Produkty</span><span>{{ number_format($subtotal / 100, 2, ',', ' ') }} zł</span></div>
                <div class="cart__row"><span>Dostawa — {{ $methods[$shipCode]['label'] }}</span><span>{{ $shipCost ? number_format($shipCost / 100, 2, ',', ' ').' zł' : 'gratis' }}</span></div>
                <div class="cart__row cart__row--total"><span>Razem</span><strong>{{ number_format($total / 100, 2, ',', ' ') }} zł</strong></div>
            </div>

            <div class="cart__actions">
                <a class="cart__back" href="{{ route('home') }}">← Kontynuuj zakupy</a>
                <form method="POST" action="{{ route('cart.checkout') }}">
                    @csrf
                    <button type="submit" class="cart__buy">Kupuję i płacę</button>
                </form>
            </div>
            <p class="cart__ship">Czas realizacji: do 3 dni roboczych · zwrot do 14 dni</p>
            <p class="cart__policy">Klikając „Kupuję i płacę" akceptujesz
                <a href="{{ asset('polityka-prywatnosci.pdf') }}" target="_blank" rel="noopener noreferrer">Politykę prywatności (PDF)</a>
                i <a href="{{ route('regulamin') }}" target="_blank" rel="noopener noreferrer">Regulamin</a>.
            </p>
        </div>
    @endif
</main>

@push('scripts')
<script>
function shipToggle(radio){
    var p = document.getElementById('shipPoint');
    if (p) p.style.display = radio.dataset.point === '1' ? '' : 'none';
}
</script>
@endpush
@endsection
