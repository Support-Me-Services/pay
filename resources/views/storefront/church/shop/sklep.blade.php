@extends('layouts.landing')

@section('title', 'Sklep — ' . config('shop.name'))
@section('meta-description', 'Sklep SupportMe — gadżety i tagi NFC. Dodaj do koszyka i zapłać online: BLIK, szybki przelew, karta.')

@push('head')
<style>
    .shop{ max-width:1080px; margin:0 auto; padding:32px 20px 64px; }
    .shop__head{ text-align:center; margin-bottom:24px; }
    .shop__head h1{ font-size:32px; margin:0 0 6px; }
    .shop__head p{ color:#4a5568; margin:0; }
    .shop__flash{ max-width:640px; margin:0 auto 20px; padding:12px 16px; border-radius:10px; font-size:15px; }
    .shop__flash.is-ok{ background:#e7f7ee; color:#1a7f45; border:1px solid #b7e4c7; }
    .shop__flash.is-err{ background:#fdeaea; color:#b02525; border:1px solid #f5c2c2; }
    .shop__grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }
    .pcard{ display:flex; flex-direction:column; background:#fff; border:1px solid #eef1f4; border-radius:16px; padding:16px; box-shadow:0 4px 14px rgba(20,40,80,.05); }
    .pcard__img{ height:150px; display:flex; align-items:center; justify-content:center; background:#f6f9fb; border-radius:12px; margin-bottom:12px; overflow:hidden; }
    .pcard__img img{ max-width:100%; max-height:100%; object-fit:contain; }
    .pcard__img.is-svg img{ width:96px; height:96px; }
    .pcard__name{ font-size:18px; margin:0 0 4px; }
    .pcard__desc{ font-size:13.5px; line-height:1.4; color:#5a6674; margin:0 0 12px; flex:1; }
    .pcard__price{ font-size:24px; font-weight:800; margin:0 0 12px; }
    .pcard__price small{ font-size:15px; font-weight:700; margin-left:3px; }
    .pcard__btn{ width:100%; padding:11px 14px; border:0; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; font-size:15px; cursor:pointer; }
    .pcard__btn:hover{ background:#1d4ed8; }
    .shop__ship{ text-align:center; color:#6b7280; font-size:13.5px; margin-top:28px; }
</style>
@endpush

@section('content')
<main class="shop">
    <div class="shop__head">
        <h1>Sklep</h1>
        <p>Gadżety i tagi NFC — dodaj do koszyka i zapłać online.</p>
    </div>

    @if(session('success'))<div class="shop__flash is-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="shop__flash is-err">{{ session('error') }}</div>@endif

    <div class="shop__grid">
        @foreach($items as $item)
            <div class="pcard">
                <div class="pcard__img{{ $item->isSvg() ? ' is-svg' : '' }}">
                    <img src="{{ asset($item->image) }}" alt="{{ $item->name }}">
                </div>
                <h2 class="pcard__name">{{ $item->name }}</h2>
                @if($item->description)<p class="pcard__desc">{{ $item->description }}</p>@endif
                <div class="pcard__price">{{ $item->pricePln() }}<small>zł</small></div>
                <form method="POST" action="{{ route('cart.add', $item->slug) }}">
                    @csrf
                    <button class="pcard__btn" type="submit">Dodaj do koszyka</button>
                </form>
            </div>
        @endforeach
    </div>

    <p class="shop__ship">Wysyłka kurierem: 1–3 dni robocze · zwrot do 14 dni</p>
</main>
@endsection
