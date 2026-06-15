@extends('layouts.panel')

@section('title', 'Mapa pokrycia')

@section('content')
    <div class="panel-title">
        <h1>Mapa pokrycia</h1>
        <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-secondary btn-sm">Parafie do obdzwonienia ↗</a>
    </div>

    {{-- Licznik parafii per województwo (z bazy potential_parishes) --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <div class="d-flex gap-1" style="flex-wrap:wrap;align-items:center">
                <span class="badge badge-success" style="font-weight:700">
                    Łącznie: {{ number_format($total, 0, ',', ' ') }}
                </span>
                @foreach($byVoivodeship as $woj => $count)
                    <a href="{{ route('panel.potential-parishes.index', ['voivodeship' => $woj]) }}"
                       class="badge badge-muted" style="text-decoration:none">
                        {{ $woj ?: 'bez województwa' }}: <strong>{{ number_format($count, 0, ',', ' ') }}</strong>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Osadzona samodzielna mapa Leaflet (public/coverage-map.html) --}}
    <iframe src="/coverage-map.html" style="width:100%;height:80vh;border:0;border-radius:12px"
            title="Mapa pokrycia parafii"></iframe>
@endsection
