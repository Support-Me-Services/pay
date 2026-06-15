@extends('layouts.panel')

@section('title', 'Sklepy')

@section('content')
    <div class="panel-title">
        <h1>Sklepy</h1>
        <a href="{{ route('panel.shops.create') }}" class="btn btn-primary btn-sm">+ Dodaj sklep</a>
    </div>

    @if(session('new_api_key'))
        <div class="alert alert-warning">
            <strong>Klucz API nowego sklepu</strong> (zapisz teraz — nie zostanie ponownie wyświetlony):<br>
            <code style="word-break:break-all;font-size:.95rem">{{ session('new_api_key') }}</code>
        </div>
    @endif

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>URL</th><th>Tryb płatności</th><th>Tagi</th><th>Przychód</th><th>Konwersja</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($shops as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['shop']->name }}</td>
                            <td><a href="{{ $row['shop']->base_url }}" target="_blank">{{ $row['shop']->base_url }}</a></td>
                            <td><span class="badge {{ $row['shop']->payment_mode === 'app2app' ? 'badge-brand' : 'badge-muted' }}">{{ $row['shop']->payment_mode }}</span></td>
                            <td>{{ $row['shop']->tags_count }}</td>
                            <td class="fw-bold">{{ \App\Services\StatsService::formatPln($row['stats']['revenue']) }}</td>
                            <td>{{ $row['stats']['conversion'] }}%</td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.tags.index', $row['shop']) }}">Tagi</a>
                                <a href="{{ route('panel.shops.edit', $row['shop']) }}">Edytuj</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Brak sklepów.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
