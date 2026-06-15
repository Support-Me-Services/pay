@extends('layouts.panel')

@section('title', 'Praca')

@section('content')
    <div class="panel-title">
        <h1>Praca — stanowiska</h1>
        <a href="{{ route('panel.positions.create') }}" class="btn btn-primary btn-sm">+ Dodaj stanowisko</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Kol.</th><th>Tytuł</th><th>Lokalizacja</th><th>Rodzaj</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($positions as $position)
                        <tr>
                            <td>{{ $position->sort }}</td>
                            <td class="fw-bold">{{ $position->title }}</td>
                            <td>{{ $position->location ?: '—' }}</td>
                            <td>{{ $position->employment_type ?: '—' }}</td>
                            <td>
                                @if($position->active)
                                    <span class="badge badge-success">aktywne</span>
                                @else
                                    <span class="badge badge-muted">nieaktywne</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.positions.edit', $position) }}">Edytuj</a>
                                <form method="POST" action="{{ route('panel.positions.toggle', $position) }}" style="display:inline">
                                    @csrf
                                    <a href="#" onclick="this.closest('form').submit(); return false;">
                                        {{ $position->active ? 'Dezaktywuj' : 'Aktywuj' }}
                                    </a>
                                </form>
                                <form method="POST" action="{{ route('panel.positions.destroy', $position) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć stanowisko?');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak stanowisk.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
