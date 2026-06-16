@extends('layouts.landing')

@section('title', 'Inwestorzy i akcjonariusze — ' . config('shop.name'))
@section('meta-description', 'Inwestorzy i akcjonariusze SupportMe — wierzymy, że kapitał może służyć dobru.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/inwestorzy.css') }}?v={{ substr(md5_file(public_path('css/inwestorzy.css')), 0, 10) }}">
@endpush

@section('content')
    <section class="inv-hero">
        <h1>Inwestorzy i akcjonariusze</h1>
    </section>

    <section class="inv-intro">
        <h2>Wierzymy, że kapitał<br>może służyć dobru!</h2>
        <p>Nasi inwestorzy wspierają nie tylko projekt technologiczny - wspierają inicjatywę, która ułatwia pomaganie, wzmacnia organizacje społeczne oraz wykorzystuje nowoczesne technologie do budowania pozytywnego wpływu.</p>
        <p>Naszym celem jest <strong>tworzenie rozwiązań, które łączą innowacje, odpowiedzialność i realne korzyści dla społeczeństwa.</strong></p>
    </section>

    <section class="inv-share">
        <h2 class="inv-share__h">Akcjonariusze</h2>
        <div class="inv-cards">
            <div class="inv-card">
                <div class="inv-card__avatar"></div>
                <div class="inv-card__name">PAWEŁ KOZŁOWSKI</div>
                <p class="inv-card__meta">Inwestycja kapitałowa: 100 000 zł<br>Wsparcie usługowe: 900 000 zł</p>
            </div>
            <div class="inv-card">
                <div class="inv-card__avatar"></div>
                <div class="inv-card__name">MARCIN LULA</div>
                <p class="inv-card__meta">Inwestycja kapitałowa: 10 000 zł</p>
            </div>
        </div>
    </section>
@endsection
