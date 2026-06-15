@extends('layouts.panel')

@section('title', 'Produkty')

@section('content')
    <div class="panel-title">
        <h1>Produkty (tagi NFC)</h1>
        <a href="{{ route('panel.products.create') }}" class="btn btn-primary btn-sm">+ Dodaj produkt</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Tag UID</th><th></th><th>Nazwa</th><th>Cena</th><th>Otwarcia</th><th>Zakupy</th><th>Przychód</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $row)
                        <tr>
                            <td><code>{{ $row['product']->tag_uid }}</code></td>
                            <td>
                                @if($row['product']->main_image)
                                    <img src="{{ asset('storage/' . $row['product']->main_image) }}" alt=""
                                         style="width:44px;height:44px;object-fit:cover;border-radius:6px">
                                @endif
                            </td>
                            <td class="fw-bold">{{ $row['product']->name }}</td>
                            <td class="nowrap">{{ $row['product']->pricePln() }} zł</td>
                            <td>{{ $row['stats']['opens'] }}</td>
                            <td>{{ $row['stats']['purchases'] }}</td>
                            <td class="fw-bold">{{ \App\Services\ShopStatsService::formatPln($row['stats']['revenue']) }}</td>
                            <td>
                                @if($row['product']->active)
                                    <span class="badge badge-success">aktywny</span>
                                @else
                                    <span class="badge badge-muted">nieaktywny</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.products.edit', $row['product']) }}">Edytuj</a>
                                <a href="{{ route('panel.products.stats', $row['product']) }}">Statystyki</a>
                                <form method="POST" action="{{ route('panel.products.toggle', $row['product']) }}" style="display:inline">
                                    @csrf
                                    <a href="#" onclick="this.closest('form').submit(); return false;">
                                        {{ $row['product']->active ? 'Dezaktywuj' : 'Aktywuj' }}
                                    </a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">Brak produktów.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
