@extends('layouts.public')

@section('title', 'Praca — ' . config('shop.name'))
@section('meta-description', 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.')

@push('head')
<style>
    .careers-hero { text-align: center; padding: 56px 20px 28px; }
    .careers-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .careers-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(2rem, 6vw, 3rem); color: var(--navy); margin: 10px 0 12px; }
    .careers-hero .lede { color: var(--ink-soft); max-width: 620px; margin: 0 auto; font-size: 1.05rem; }

    .job-list { max-width: 760px; margin: 0 auto; padding: 0 18px 64px; display: grid; gap: 18px; }
    .job-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm); padding: 24px 24px 22px; }
    .job-card h2 { font-family: var(--display); font-weight: 600; font-size: 1.4rem; color: var(--navy); margin: 0 0 10px; }
    .job-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .job-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 600;
        color: var(--gold-deep); background: #f7efdc; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; }
    .job-desc { color: var(--ink-soft); font-size: .98rem; line-height: 1.6; margin-bottom: 18px; }
    .job-desc :is(p, ul, ol) { margin: 0 0 .6em; }
    .job-desc ul, .job-desc ol { padding-left: 1.2em; }
    .job-empty { text-align: center; color: var(--muted); padding: 40px 0; }
</style>
@endpush

@section('content')
    <section class="careers-hero">
        <div class="eyebrow">Dołącz do nas</div>
        <h1>Praca i wolontariat</h1>
        <p class="lede">Szukamy osób, które chcą tworzyć coś dobrego razem z nami. Poniżej znajdziesz aktualne oferty — kliknij „Aplikuj", a odezwiemy się.</p>
    </section>

    <div class="job-list">
        @forelse($positions as $position)
            <article class="job-card">
                <h2>{{ $position->title }}</h2>
                @if($position->location || $position->employment_type)
                    <div class="job-chips">
                        @if($position->location)
                            <span class="job-chip">📍 {{ $position->location }}</span>
                        @endif
                        @if($position->employment_type)
                            <span class="job-chip">🕒 {{ $position->employment_type }}</span>
                        @endif
                    </div>
                @endif
                @if($position->description_html)
                    <div class="job-desc">{!! $position->description_html !!}</div>
                @endif
                <a href="{{ route('contact.show', ['stanowisko' => $position->title]) }}" class="btn btn-gold">Aplikuj</a>
            </article>
        @empty
            <p class="job-empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>
        @endforelse
    </div>
@endsection
