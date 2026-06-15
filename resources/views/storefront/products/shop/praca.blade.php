@extends('layouts.public')

@section('title', 'Praca — ' . config('shop.name'))

@section('content')
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:760px">
            <h1 style="font-size:1.7rem">Praca i wolontariat</h1>
            <p class="text-muted mb-3">Aktualne oferty. Kliknij „Aplikuj", a odezwiemy się.</p>

            <div style="display:grid;gap:16px">
                @forelse($positions as $position)
                    <div class="card" style="padding:20px">
                        <h2 style="font-size:1.25rem;margin:0 0 8px">{{ $position->title }}</h2>
                        @if($position->location || $position->employment_type)
                            <p class="text-muted" style="margin:0 0 10px">
                                @if($position->location){{ $position->location }}@endif
                                @if($position->location && $position->employment_type) · @endif
                                @if($position->employment_type){{ $position->employment_type }}@endif
                            </p>
                        @endif
                        @if($position->description_html)
                            <div style="margin-bottom:14px">{!! $position->description_html !!}</div>
                        @endif
                        <a href="{{ route('careers.apply', $position) }}" class="btn btn-primary">Aplikuj</a>
                    </div>
                @empty
                    <p class="text-muted">Obecnie nie prowadzimy rekrutacji.</p>
                @endforelse

                <div style="margin-top:8px">
                    <p class="text-muted" style="margin:0 0 8px">Nie znalazłeś oferty dla siebie?</p>
                    <a href="{{ route('careers.apply.general') }}" class="btn btn-secondary">Aplikuj spontanicznie</a>
                </div>
            </div>
        </div>
    </section>
@endsection
