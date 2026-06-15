@extends('layouts.public')

@section('title', config('shop.name') . ' — wesprzyj swój kościół')

@section('content')
    <section class="hero">
        <div class="eyebrow">Cyfrowa taca</div>
        <h1>Złóż <em>tacę</em><br>nie wyjmując portfela</h1>
        <p class="lede">Przyłóż telefon do tabliczki przy ołtarzu albo wybierz parafię z listy poniżej. Wpłata trafia w całości do kościoła.</p>
        <a href="#parafie" class="scroll-cue">
            wybierz parafię
            <span aria-hidden="true"></span>
        </a>
        {{-- arkadowy dół hero --}}
        <svg class="hero-arches" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,60 V30 C150,-10 250,-10 400,30 C550,-10 650,-10 800,30 C950,-10 1050,-10 1200,30 V60 Z"/>
        </svg>
    </section>

    <section class="section" id="parafie">
        <div class="container container-wide">
            <div class="section-head">
                <h2>Wybierz parafię</h2>
                <p>Każdy kościół ma własną tabliczkę NFC przy tacy. Tu znajdziesz te same parafie online.</p>
            </div>

            <div class="parish-list">
                @forelse($products as $product)
                    <a href="{{ route('product.show', $product->slug) }}" class="parish">
                        <div class="parish-art">
                            @if($product->main_image)
                                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" loading="lazy">
                            @endif
                            @if($product->city)
                                <span class="parish-city">{{ $product->city }}</span>
                            @endif
                            <span class="parish-name-over">{{ $product->name }}</span>
                        </div>
                        <div class="parish-body">
                            @if($product->purpose)
                                <span class="parish-purpose">✦ {{ $product->purpose }}</span>
                            @endif
                            <div class="parish-cta">
                                <span class="from">już od <b>{{ $product->pricePln() }} zł</b></span>
                                <span class="go">Wesprzyj <span class="arrow">→</span></span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="muted">Brak parafii.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="trust">
        <div class="row">
            <div class="item">
                <div class="k">BLIK</div>
                <div class="v">Płać aplikacją banku — bez przepisywania numerów kart.</div>
            </div>
            <div class="item">
                <div class="k">100%</div>
                <div class="v">Cała kwota trafia do wybranej parafii.</div>
            </div>
            <div class="item">
                <div class="k">3&nbsp;sek.</div>
                <div class="v">Tyle zajmuje złożenie cyfrowej tacy.</div>
            </div>
        </div>
    </section>
@endsection
