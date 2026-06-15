@extends('layouts.panel')

@section('title', 'Statystyki')

@section('content')
    <div class="panel-title">
        <h1>Statystyki</h1>
    </div>

    <form method="GET" action="{{ route('panel.stats') }}" class="card card-static mb-3">
        <div class="card-body d-flex gap-2" style="flex-wrap:wrap;align-items:flex-end">
            <div style="min-width:220px">
                <label for="shop_id">Sklep</label>
                <select id="shop_id" name="shop_id" onchange="this.form.submit()">
                    <option value="">— wszystkie sklepy —</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected($shop && $shop->id === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($shop)
                <div style="min-width:220px">
                    <label for="tag_id">Tag</label>
                    <select id="tag_id" name="tag_id" onchange="this.form.submit()">
                        <option value="">— wszystkie tagi —</option>
                        @foreach($tags as $t)
                            <option value="{{ $t->id }}" @selected($tag && $tag->id === $t->id)>{{ $t->tag_uid }} {{ $t->label ? '(' . $t->label . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </form>

    <h3 class="mb-1">Łącznie {{ $shop ? '— ' . $shop->name : '' }} {{ $tag ? '— ' . $tag->tag_uid : '' }}</h3>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Otwarcia tagów</div><div class="stat-value">{{ $total['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Płatności rozpoczęte</div><div class="stat-value">{{ $total['started'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Opłacone</div><div class="stat-value text-success">{{ $total['paid'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Nieudane</div><div class="stat-value text-error">{{ $total['failed'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $total['conversion'] }}%</div></div>
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value">{{ \App\Services\StatsService::formatPln($total['revenue']) }}</div></div>
    </div>

    <h3 class="mb-1">Ostatnie 30 dni</h3>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Otwarcia</div><div class="stat-value">{{ $last30['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Rozpoczęte</div><div class="stat-value">{{ $last30['started'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Opłacone</div><div class="stat-value text-success">{{ $last30['paid'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Nieudane</div><div class="stat-value text-error">{{ $last30['failed'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $last30['conversion'] }}%</div></div>
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value">{{ \App\Services\StatsService::formatPln($last30['revenue']) }}</div></div>
    </div>

    <div class="chart-card">
        <h3>Opłacone transakcje — dzień po dniu (30 dni)</h3>
        <canvas id="dailyChart" height="90"></canvas>
    </div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: @json($series['labels']),
        datasets: [{
            label: 'Opłacone',
            data: @json($series['counts']),
            backgroundColor: '#E20074',
            borderRadius: 4,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
@endpush
