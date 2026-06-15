@extends('layouts.public')

@section('title', config('shop.name') . ' — produkty')

@section('content')
    <section class="section" style="padding-top:32px">
        <div class="container">
            <h1 style="font-size:1.7rem">Nasze produkty</h1>
            <p class="text-muted mb-3">Zeskanuj tag NFC przy produkcie albo wybierz z listy.</p>

            <div class="product-grid">
                @forelse($products as $product)
                    <a href="{{ route('product.show', $product->slug) }}" class="card product-card">
                        <div class="thumb">
                            @if($product->main_image)
                                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}">
                            @endif
                        </div>
                        <div class="info">
                            <span class="name">{{ $product->name }}</span>
                            <span class="price">{{ $product->pricePln() }} zł</span>
                        </div>
                    </a>
                @empty
                    <p class="text-muted">Brak produktów.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
