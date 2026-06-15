@extends('layouts.panel')

@section('title', 'Statystyki — ' . $product->name)

@section('content')
    <div class="panel-title">
        <h1>Statystyki: {{ $product->name }}</h1>
        <a href="{{ route('panel.products.index') }}" class="btn btn-secondary btn-sm">← Produkty</a>
    </div>

    <h3 class="mb-1">Łącznie</h3>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Otwarcia tagów</div><div class="stat-value">{{ $total['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Wejścia na stronę</div><div class="stat-value">{{ $total['views'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Kliki „Kup"</div><div class="stat-value">{{ $total['clicks'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Zakupy</div><div class="stat-value text-success">{{ $total['purchases'] }}</div></div>
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value">{{ \App\Services\ShopStatsService::formatPln($total['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $total['conversion'] }}%</div><div class="stat-sub">zakupy / otwarcia</div></div>
    </div>

    <h3 class="mb-1">Ostatnie 30 dni</h3>
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Otwarcia</div><div class="stat-value">{{ $last30['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Wejścia</div><div class="stat-value">{{ $last30['views'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Kliki „Kup"</div><div class="stat-value">{{ $last30['clicks'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Zakupy</div><div class="stat-value text-success">{{ $last30['purchases'] }}</div></div>
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value">{{ \App\Services\ShopStatsService::formatPln($last30['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $last30['conversion'] }}%</div></div>
    </div>

    <div class="chart-card">
        <h3>Lejek sprzedażowy (łącznie)</h3>
        @php
            $max = max(1, $total['opens']);
            $funnel = [
                ['label' => 'Otwarcia tagów', 'value' => $total['opens']],
                ['label' => 'Kliki „Kup"', 'value' => $total['clicks']],
                ['label' => 'Zakupy', 'value' => $total['purchases']],
            ];
        @endphp
        <div class="funnel">
            @foreach($funnel as $step)
                <div class="funnel-row">
                    <div class="funnel-label">{{ $step['label'] }}</div>
                    <div class="funnel-bar-wrap">
                        <div class="funnel-bar" style="width:{{ max(4, round($step['value'] / $max * 100)) }}%">{{ $step['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="chart-card">
        <h3>Zakupy — dzień po dniu (30 dni)</h3>
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
            label: 'Zakupy',
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
