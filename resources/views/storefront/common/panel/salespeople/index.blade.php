@extends('layouts.panel')

@section('title', 'Handlowcy')

@section('content')
    <div class="panel-title">
        <h1>Handlowcy</h1>
        <a href="{{ route('panel.salespeople.create') }}" class="btn btn-primary btn-sm">+ Dodaj handlowca</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Imię i nazwisko</th><th>Kontakt</th><th>Województwa</th><th>Parafie</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($salespeople as $sp)
                        <tr>
                            <td class="fw-bold">{{ $sp->name }}</td>
                            <td>
                                {{ $sp->email ?: '—' }}
                                @if($sp->phone)<br><span class="text-muted" style="font-weight:400">{{ $sp->phone }}</span>@endif
                            </td>
                            <td>
                                @if($sp->voivodeships)
                                    {{ implode(', ', $sp->voivodeships) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($sp->parishes_count > 0)
                                    <a href="{{ route('panel.products.index', ['q' => $sp->name]) }}">{{ $sp->parishes_count }}</a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                @if($sp->active)
                                    <span class="badge badge-success">aktywny</span>
                                @else
                                    <span class="badge badge-muted">nieaktywny</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.salespeople.edit', $sp) }}">Edytuj</a>
                                <form method="POST" action="{{ route('panel.salespeople.destroy', $sp) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć handlowca? Parafie stracą przypisanie.');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak handlowców.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
