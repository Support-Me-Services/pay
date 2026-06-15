@extends('layouts.panel')

@section('title', 'Dashboard')

@section('content')
    <div class="panel-title">
        <h1>Dashboard</h1>
        <span class="badge badge-brand">tryb płatności: {{ config('shop.payment_mode') }}</span>
    </div>

    @php
        $dashUnread = \App\Modules\Storefront\Models\ContactMessage::where('is_read', false)->count();
    @endphp
    @if($dashUnread > 0)
        <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <span>Masz <strong>{{ $dashUnread }}</strong> nieprzeczytanych wiadomości z formularza kontaktowego.</span>
            <a href="{{ route('panel.messages.index') }}" class="btn btn-primary btn-sm">Zobacz wiadomości</a>
        </div>
    @endif

    <h3 class="mb-1">Łącznie</h3>
    <div class="stat-grid">
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value">{{ \App\Services\ShopStatsService::formatPln($total['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Zakupy</div><div class="stat-value">{{ $total['purchases'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia tagów</div><div class="stat-value">{{ $total['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value">{{ $total['conversion'] }}%</div><div class="stat-sub">zakupy / otwarcia</div></div>
    </div>

    <h3 class="mb-1">Ostatnie 30 dni</h3>
    <div class="stat-grid">
        <div class="stat-card stat-brand"><div class="stat-label">Przychód 30 dni</div><div class="stat-value">{{ \App\Services\ShopStatsService::formatPln($last30['revenue']) }}</div></div>
        <div class="stat-card"><div class="stat-label">Zakupy 30 dni</div><div class="stat-value">{{ $last30['purchases'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia 30 dni</div><div class="stat-value">{{ $last30['opens'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Konwersja 30 dni</div><div class="stat-value">{{ $last30['conversion'] }}%</div></div>
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
