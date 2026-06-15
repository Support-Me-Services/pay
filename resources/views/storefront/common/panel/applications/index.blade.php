@extends('layouts.panel')

@section('title', 'Aplikacje')

@section('content')
    <div class="panel-title">
        <h1>Aplikacje
            @if($unread > 0)
                <span class="badge badge-brand">{{ $unread }} nowych</span>
            @endif
        </h1>
    </div>

    @php $totalApps = array_sum($statusCounts->all()); $base = $filterPosition ? ['position' => $filterPosition->id] : []; @endphp
    <div class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <a href="{{ route('panel.applications.index', $base) }}"
           class="btn btn-sm {{ $status ? 'btn-secondary' : 'btn-primary' }}">Wszystkie ({{ $totalApps }})</a>
        @foreach(\App\Modules\Storefront\Models\JobApplication::STATUSES as $key => $label)
            <a href="{{ route('panel.applications.index', $base + ['status' => $key]) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}">
                {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
            </a>
        @endforeach
    </div>

    @if($filterPosition)
        <div class="alert" style="background:#f7efdc;border:1px solid var(--line,#e2e8f0)">
            Filtr: oferta „{{ $filterPosition->title }}".
            <a href="{{ route('panel.applications.index') }}">Pokaż wszystkie</a>
        </div>
    @endif

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th><th>Kandydat</th><th>Oferta</th><th>CV</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($applications as $application)
                        <tr style="{{ $application->is_read ? '' : 'font-weight:700' }}">
                            <td class="nowrap">{{ $application->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $application->name }}<br>
                                <span class="text-muted" style="font-weight:400">{{ $application->email }}</span>
                            </td>
                            <td>{{ $application->position?->title ?: 'aplikacja spontaniczna' }}</td>
                            <td>
                                @if($application->cv_path)
                                    <a href="{{ route('panel.applications.cv', $application) }}">Pobierz</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php [$bg, $fg] = $application->statusColors(); @endphp
                                <span class="badge" style="background:{{ $bg }};color:{{ $fg }};font-weight:600">{{ $application->statusLabel() }}</span>
                                @unless($application->is_read)
                                    <span class="badge badge-success" style="margin-left:4px">nowe</span>
                                @endunless
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.applications.show', $application) }}">Otwórz</a>
                                <form method="POST" action="{{ route('panel.applications.destroy', $application) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć zgłoszenie?');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak zgłoszeń.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
