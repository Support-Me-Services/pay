@extends('layouts.landing')

@section('title', $category['label_text'] . ' — SupportME')

@section('content')
    <section class="lp-section lp-catpage lp-container">
        <a class="lp-back" href="{{ route('home') }}#kogo-wspieramy">← Wszystkie kategorie</a>
        <div class="lp-head">
            <h2>{{ $category['label_text'] }}</h2>
            <p>{{ $category['intro'] }}</p>
        </div>

        @if($products->isNotEmpty())
            <div class="lp-grid">
                @foreach($products as $product)
                    <a class="lp-pcard" href="{{ route('product.show', $product->slug) }}">
                        <div class="lp-pcard__art">
                            @if($product->main_image)
                                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" loading="lazy">
                            @endif
                            @if($product->city)
                                <span class="lp-pcard__city">{{ $product->city }}</span>
                            @endif
                        </div>
                        <div class="lp-pcard__body">
                            <span class="lp-pcard__name">{{ $product->name }}</span>
                            @if($product->purpose)
                                <span class="lp-pcard__purpose">{{ $product->purpose }}</span>
                            @endif
                            <span class="lp-pcard__cta">
                                <span>już od <b>{{ $product->pricePln() }} zł</b></span>
                                <b>Wesprzyj →</b>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="lp-empty">
                W tej kategorii nie ma jeszcze organizacji.<br>
                Pracujemy nad tym — w międzyczasie zobacz
                <a href="{{ route('category', 'miejsca-kultu') }}">miejsca kultu i organizacje religijne</a>,
                które już wspieramy.
            </p>
        @endif
    </section>
@endsection
