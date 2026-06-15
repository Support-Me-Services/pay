@extends('layouts.public')

@section('title', $product->name . ' — ' . config('shop.name'))
@section('body-class', 'has-sticky-buy')

@section('content')
    <section class="section" style="padding-top:24px">
        <div class="container" style="max-width:760px">
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="card card-static" style="overflow:hidden">
                <div class="thumb" style="aspect-ratio:4/3;background:var(--bg-alt)">
                    @if($product->main_image)
                        <img id="mainImage" src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}"
                             style="width:100%;height:100%;object-fit:cover">
                    @endif
                </div>
            </div>

            @if($product->images->count())
                <div class="gallery-thumbs">
                    @if($product->main_image)
                        <img src="{{ asset('storage/' . $product->main_image) }}" class="active"
                             onclick="swapImage(this)" alt="{{ $product->name }}">
                    @endif
                    @foreach($product->images as $image)
                        <img src="{{ asset('storage/' . $image->path) }}" onclick="swapImage(this)" alt="{{ $product->name }}">
                    @endforeach
                </div>
            @endif

            <h1 style="font-size:1.6rem" class="mt-3">{{ $product->name }}</h1>
            <div class="price-xl text-brand mb-2">{{ $product->pricePln() }} zł</div>

            <div class="prose">{!! $product->description_html !!}</div>
        </div>
    </section>

    <div class="sticky-buy">
        <div class="container" style="max-width:760px;padding:0">
            <form method="POST" action="{{ route('product.buy', $product->slug) }}" id="buyForm">
                @csrf
                <button type="submit" class="btn btn-primary btn-block" style="font-size:1.15rem;padding:16px">
                    Kup teraz — {{ $product->pricePln() }} zł
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function swapImage(el) {
        document.getElementById('mainImage').src = el.src;
        document.querySelectorAll('.gallery-thumbs img').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
    }

    // Zapobiega podwójnemu klikowi "Kup"
    document.getElementById('buyForm').addEventListener('submit', function () {
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.textContent = 'Przenosimy do płatności...';
    });
</script>
@endpush
