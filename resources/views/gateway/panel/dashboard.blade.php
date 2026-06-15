@extends('layouts.panel')

@section('title', 'Dashboard')

@section('content')
    <div class="panel-title">
        <h1>Dashboard</h1>
    </div>

    <h3 class="mb-1">Łącznie</h3>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Sklepy</div><div class="stat-value">{{ $shops->count() }}</div></div>
        <div class="stat-card stat-brand"><div class="stat-label">Suma opłaconych</div><div class="stat-value">{{ \App\Services\StatsService::formatPln($global['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Płatności opłacone</div><div class="stat-value">{{ $global['paid'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia tagów</div><div class="stat-value">{{ $global['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $global['conversion'] }}%</div><div class="stat-sub">zakupy / otwarcia</div></div>
    </div>

    <h3 class="mb-1">Ostatnie 30 dni</h3>
    <div class="stat-grid">
        <div class="stat-card stat-brand"><div class="stat-label">Przychód 30 dni</div><div class="stat-value">{{ \App\Services\StatsService::formatPln($global30['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Opłacone 30 dni</div><div class="stat-value">{{ $global30['paid'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia 30 dni</div><div class="stat-value">{{ $global30['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja 30 dni</div><div class="stat-value">{{ $global30['conversion'] }}%</div></div>
    </div>

    <div class="chart-card">
        <h3>Opłacone transakcje — dzień po dniu (30 dni)</h3>
        <canvas id="dailyChart" height="90"></canvas>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <h3>Sklepy</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Sklep</th><th>Tryb</th><th>Tagi</th><th>Otwarcia</th><th>Zakupy</th><th>Przychód</th><th>Konwersja</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($perShop as $row)
                        <tr>
                            <td class="fw-bold"><a href="{{ route('panel.tags.index', $row['shop']) }}">{{ $row['shop']->name }}</a></td>
                            <td><span class="badge {{ $row['shop']->payment_mode === 'app2app' ? 'badge-brand' : 'badge-muted' }}">{{ $row['shop']->payment_mode }}</span></td>
                            <td>{{ $row['shop']->tags_count }}</td>
                            <td>{{ $row['stats']['opens'] }}</td>
                            <td>{{ $row['stats']['paid'] }}</td>
                            <td class="fw-bold">{{ \App\Services\StatsService::formatPln($row['stats']['revenue']) }}</td>
                            <td>{{ $row['stats']['conversion'] }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: @json($series['labels']),
        datasets: [{
            label: 'Opłacone transakcje',
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
