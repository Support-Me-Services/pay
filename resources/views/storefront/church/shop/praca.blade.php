@extends('layouts.landing')

@section('title', 'Praca — ' . config('shop.name'))
@section('meta-description', 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
@endpush

@section('content')
    {{-- SUB-HERO „Pracuj z nami!" (1:1 wg Figmy REKRUTACJA) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <h1>Pracuj z nami!</h1>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-careers">
            {{-- Nagłówek z grafiką zespołu (okrągły kadr) + treścią rekrutacji --}}
            <div class="sp-careers__head">
                <div class="sp-circle sp-circle--lg sp-careers__photo">
                    <img src="{{ asset('img/landing/praca.jpg') }}" alt="Zespół SupportME przy pracy" loading="lazy">
                </div>
                <div class="sp-careers__head-body">
                    <h2>Tworzymy coś dobrego — razem</h2>
                    <p>Szukamy osób, które chcą łączyć technologię z realnym wpływem na świat. Budujemy płatności NFC, które pomagają parafiom, fundacjom i lokalnym inicjatywom zbierać wsparcie prościej niż kiedykolwiek. Rozwiń ofertę i wyślij zgłoszenie wraz z CV — odezwiemy się.</p>
                </div>
            </div>

            <div class="sp-jobs">
                @forelse($positions as $position)
                    @php
                        // Wykryj „zdalną" z tekstu lokalizacji / formy zatrudnienia (model nie ma pola is_remote).
                        $haystack = mb_strtolower(trim(($position->location ?? '') . ' ' . ($position->employment_type ?? '')));
                        $isRemote = $haystack !== '' && (str_contains($haystack, 'zdaln') || str_contains($haystack, 'remote') || str_contains($haystack, 'hybryd'));
                        // Skrót opisu: zdejmij HTML, przytnij. Placeholder spójny z projektem, gdy pusto/krótko.
                        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($position->description_html ?? '')));
                        $excerpt = mb_strlen($plain) >= 40
                            ? \Illuminate\Support\Str::limit($plain, 180)
                            : 'Dołącz do zespołu, który tworzy płatności NFC dla dobra wspólnego — technologię wspierającą parafie, fundacje i lokalne inicjatywy. Szukamy osób, które chcą łączyć nowoczesne rozwiązania z realnym wpływem na ludzi.';
                    @endphp
                    <article class="sp-job sp-job--rich" id="oferta-{{ $position->id }}">
                        <h2>{{ $position->title }}</h2>
                        <div class="sp-job__chips">
                            @if($position->employment_type)<span class="sp-job__chip">{{ $position->employment_type }}</span>@endif
                            @if($position->location)<span class="sp-job__chip">{{ $position->location }}</span>@endif
                            @if($isRemote)<span class="sp-job__chip sp-job__chip--remote">Praca zdalna</span>@endif
                        </div>

                        <p class="sp-job__excerpt">{{ $excerpt }}</p>

                        <div class="sp-job__foot">
                            <a class="sp-btn sp-btn--cta" href="{{ route('careers.show', $position) }}">Zobacz więcej</a>
                        </div>
                    </article>
                @empty
                    <p class="sp-job--empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
