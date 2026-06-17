@extends('layouts.panel')

@section('title', 'Sklep — produkty')

@section('content')
    <div class="panel-title">
        <h1>Sklep — produkty (NFC)</h1>
        <a href="{{ route('panel.shop-items.create') }}" class="btn btn-primary btn-sm">+ Dodaj produkt</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Kol.</th><th>Grafika</th><th>Nazwa</th><th>Min. kwota</th><th>Tag NFC</th><th>Domyślny</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->sort }}</td>
                            <td>
                                @if($item->image)
                                    <img src="{{ asset($item->image) }}" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:6px;background:#f6f9fb">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="fw-bold">{{ $item->name }}</td>
                            <td>{{ $item->minAmountPln() }} zł</td>
                            <td>{{ $item->tag_uid ?: '—' }}</td>
                            <td>
                                @if($item->is_default)
                                    <span class="badge badge-brand">domyślny</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->active)
                                    <span class="badge badge-success">aktywny</span>
                                @else
                                    <span class="badge badge-muted">nieaktywny</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.shop-items.edit', $item) }}">Edytuj</a>
                                <form method="POST" action="{{ route('panel.shop-items.toggle', $item) }}" style="display:inline">
                                    @csrf
                                    <a href="#" onclick="this.closest('form').submit(); return false;">
                                        {{ $item->active ? 'Dezaktywuj' : 'Aktywuj' }}
                                    </a>
                                </form>
                                <form method="POST" action="{{ route('panel.shop-items.destroy', $item) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć produkt?');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">Brak produktów.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
