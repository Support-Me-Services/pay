@extends('layouts.panel')

@section('title', 'Parafie')

@section('content')
    <div class="panel-title">
        <h1>Parafie (tagi NFC)</h1>
        <a href="{{ route('panel.products.create') }}" class="btn btn-primary btn-sm">+ Dodaj parafię</a>
    </div>

    {{-- Filtr po statusie (zakładki + liczniki) --}}
    <div class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <a href="{{ route('panel.products.index', array_filter(['q' => $q])) }}"
           class="btn btn-sm {{ $status ? 'btn-secondary' : 'btn-primary' }}">Wszystkie ({{ $total }})</a>
        @foreach(\App\Modules\Storefront\Models\Product::STATUSES as $key => $label)
            <a href="{{ route('panel.products.index', array_filter(['status' => $key, 'q' => $q])) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">
                {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
            </a>
        @endforeach
    </div>

    {{-- Wyszukiwarka po nazwie / mieście / województwie --}}
    <form method="GET" action="{{ route('panel.products.index') }}" class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="text" name="q" value="{{ $q }}" placeholder="Szukaj: nazwa, miasto, województwo…"
               style="flex:1;min-width:240px;max-width:420px">
        <button type="submit" class="btn btn-primary btn-sm">Szukaj</button>
        @if($q !== '')
            <a href="{{ route('panel.products.index', array_filter(['status' => $status])) }}" class="btn btn-secondary btn-sm">Wyczyść</a>
        @endif
    </form>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Miasto / województwo</th><th>Status</th><th>Handlowiec</th>
                        <th>Tag NFC</th><th>Wpłaty</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parishes as $parish)
                        <tr>
                            <td class="fw-bold">{{ $parish->name }}</td>
                            <td>
                                {{ $parish->city ?: '—' }}
                                @if($parish->voivodeship)
                                    <br><span class="text-muted" style="font-weight:400">{{ $parish->voivodeship }}</span>
                                @endif
                            </td>
                            <td>
                                @php [$bg, $fg] = $parish->statusColors(); @endphp
                                <span class="badge" style="background:{{ $bg }};color:{{ $fg }};font-weight:600">{{ $parish->statusLabel() }}</span>
                            </td>
                            <td>{{ $parish->salesperson?->name ?: '—' }}</td>
                            <td><code>{{ $parish->tag_uid }}</code></td>
                            <td>{{ $parish->orders_count }}</td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.products.edit', $parish) }}">Edytuj</a>
                                <a href="{{ route('panel.products.stats', $parish) }}">Statystyki</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Brak parafii spełniających kryteria.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
