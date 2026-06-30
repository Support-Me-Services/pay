{{-- SP-CHURCH-THANKS: podziekowanie (storefront/church/shop/dziekujemy.blade.php) --}}
@extends('layouts.landing')

@section('title', 'Dziękujemy — ' . config('shop.name'))
@section('meta-description', 'Dziękujemy za Twoje wsparcie i zaufanie. Twoja wiadomość do nas dotarła — odezwiemy się najszybciej, jak to możliwe.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
@endpush

@section('content')
    <!-- SP-CHURCH-THANKS: storefront/church/shop/dziekujemy.blade.php -->
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <p class="sp-eyebrow">Dziękujemy</p>
            <h1>Dziękujemy za Twoje wsparcie!</h1>
            <p class="sp-lede">Twoja wiadomość do nas dotarła. To dzięki takim osobom jak Ty możemy
                wspierać parafie, fundacje i&nbsp;lokalne społeczności.</p>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-doc">
            <article class="sp-legal__body">
                <section class="sp-legal__section">
                    <h2>Co dalej?</h2>
                    <ol>
                        <li>Nasz zespół zapozna się z&nbsp;Twoim zgłoszeniem.</li>
                        <li>Skontaktujemy się z&nbsp;Tobą — zwykle w&nbsp;ciągu jednego dnia roboczego.</li>
                        <li>Wspólnie ustalimy kolejne kroki współpracy lub wsparcia.</li>
                    </ol>
                </section>
                <section class="sp-legal__section">
                    <h2>Masz pytania?</h2>
                    <p>Napisz do nas na adres
                        <a href="mailto:office@please-support-me.com">office@please-support-me.com</a>
                        — chętnie odpowiemy i&nbsp;pomożemy.</p>
                    <p style="margin-top:1.5rem">
                        <a href="{{ route('home') }}">← Wróć na stronę główną</a>
                    </p>
                </section>
            </article>
        </div>
    </div>
@endsection
