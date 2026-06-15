@extends('layouts.panel')

@section('title', 'Mapa pokrycia')

@section('content')
    <div class="panel-title">
        <h1>Mapa pokrycia</h1>
        <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-secondary btn-sm">Parafie do obdzwonienia ↗</a>
    </div>

    {{-- Pasek statusów: te same statusy co w „Parafie do obdzwonienia”, z licznikami.
         Każdy link prowadzi do listy przefiltrowanej po danym statusie (obdzwanianie). --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <div class="text-muted mb-1" style="font-size:.8rem;font-weight:600">Statusy — kliknij, aby przejść do listy do obdzwonienia</div>
            <div class="d-flex gap-1" style="flex-wrap:wrap">
                <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-sm btn-primary">
                    Wszystkie ({{ number_format($total, 0, ',', ' ') }})
                </a>
                @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                    <a href="{{ route('panel.potential-parishes.index', ['status' => $key]) }}" class="btn btn-sm btn-secondary">
                        {{ $label }} ({{ number_format($statusCounts[$key] ?? 0, 0, ',', ' ') }})
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Licznik parafii per województwo (z bazy potential_parishes) — skrót do listy po woj. --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <div class="text-muted mb-1" style="font-size:.8rem;font-weight:600">Województwa — kliknij, aby przefiltrować listę</div>
            <div class="d-flex gap-1" style="flex-wrap:wrap;align-items:center">
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
