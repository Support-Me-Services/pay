@extends('layouts.panel')

@section('title', 'Parafie do obdzwonienia')

@section('content')
    <div class="panel-title">
        <h1>Parafie do obdzwonienia</h1>
        <a href="{{ route('panel.coverage.map') }}" class="btn btn-secondary btn-sm">Mapa pokrycia ↗</a>
    </div>

    {{-- Liczniki: łączny + per status (klikalne jako filtr) --}}
    <div class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <a href="{{ route('panel.potential-parishes.index', array_filter(['voivodeship' => $voivodeship, 'city' => $city, 'name' => $name, 'salesperson_id' => $salespersonId])) }}"
           class="btn btn-sm {{ $status ? 'btn-secondary' : 'btn-primary' }}">Wszystkie ({{ number_format($total, 0, ',', ' ') }})</a>
        @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
            <a href="{{ route('panel.potential-parishes.index', array_filter(['status' => $key, 'voivodeship' => $voivodeship, 'city' => $city, 'name' => $name, 'salesperson_id' => $salespersonId])) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">
                {{ $label }} ({{ number_format($statusCounts[$key] ?? 0, 0, ',', ' ') }})
            </a>
        @endforeach
    </div>

    {{-- Pasek filtrów: województwo, miasto, nazwa, status, handlowiec --}}
    <div class="card card-static mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('panel.potential-parishes.index') }}" class="d-flex gap-1" style="flex-wrap:wrap;align-items:flex-end">
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Województwo</label>
                    <select name="voivodeship" style="min-width:180px">
                        <option value="">— wszystkie —</option>
                        @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $woj)
                            <option value="{{ $woj }}" @selected($voivodeship === $woj)>{{ $woj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Miasto</label>
                    <input type="text" name="city" value="{{ $city }}" placeholder="Szukaj miasta…" style="min-width:160px">
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Nazwa</label>
                    <input type="text" name="name" value="{{ $name }}" placeholder="Szukaj nazwy…" style="min-width:200px">
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Status</label>
                    <select name="status" style="min-width:160px">
                        <option value="">— wszystkie —</option>
                        @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Handlowiec</label>
                    <select name="salesperson_id" style="min-width:180px">
                        <option value="">— wszyscy —</option>
                        @foreach($salespeople as $sp)
                            <option value="{{ $sp->id }}" @selected($salespersonId === $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-sm">Filtruj</button>
                    <a href="{{ route('panel.potential-parishes.index') }}" class="btn btn-secondary btn-sm">Wyczyść</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Miasto</th><th>Woj.</th><th>Status</th><th>Handlowiec</th><th>Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parishes as $parish)
                        <tr>
                            <td class="fw-bold">
                                {{ $parish->name }}
                                @if($parish->denomination)
                                    <br><span class="text-muted" style="font-weight:400;font-size:.78rem">{{ $parish->denomination }}</span>
                                @endif
                            </td>
                            <td>{{ $parish->city ?: '—' }}</td>
                            <td>{{ $parish->voivodeship ?: '—' }}</td>
                            <td>
                                @php [$bg, $fg] = $parish->statusColors(); @endphp
                                <span class="badge" style="background:{{ $bg }};color:{{ $fg }};font-weight:600">{{ $parish->statusLabel() }}</span>
                                @if($parish->called_at)
                                    <br><span class="text-muted" style="font-size:.72rem">{{ $parish->called_at->format('d.m.Y') }}</span>
                                @endif
                            </td>
                            <td>{{ $parish->salesperson?->name ?: '—' }}</td>
                            <td class="actions nowrap">
                                <a href="#" onclick="document.getElementById('row-{{ $parish->id }}').classList.toggle('hidden'); return false;">Zmień status</a>
                            </td>
                        </tr>
                        {{-- Wiersz edycji inline (form): status + handlowiec + notatka --}}
                        <tr id="row-{{ $parish->id }}" class="hidden">
                            <td colspan="6" style="background:#fafbfc">
                                <form method="POST" action="{{ route('panel.potential-parishes.status', $parish) }}"
                                      class="d-flex gap-1" style="flex-wrap:wrap;align-items:flex-end">
                                    @csrf
                                    <div>
                                        <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Status</label>
                                        <select name="status" style="min-width:160px">
                                            @foreach(\App\Modules\Storefront\Models\PotentialParish::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected($parish->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Handlowiec</label>
                                        <select name="salesperson_id" style="min-width:180px">
                                            <option value="">— brak —</option>
                                            @foreach($salespeople as $sp)
                                                <option value="{{ $sp->id }}" @selected($parish->salesperson_id === $sp->id)>{{ $sp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="flex:1;min-width:240px">
                                        <label class="text-muted" style="display:block;font-size:.78rem;margin-bottom:2px">Notatka</label>
                                        <input type="text" name="note" value="{{ $parish->note }}" placeholder="Notatka z rozmowy…" style="width:100%">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-sm">Zapisz</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak parafii spełniających kryteria.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $parishes->links() }}</div>
        </div>
    </div>

    <style>tr.hidden { display: none; }</style>
@endsection
