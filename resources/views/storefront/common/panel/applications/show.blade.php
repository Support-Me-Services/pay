@extends('layouts.panel')

@section('title', 'Zgłoszenie')

@section('content')
    <div class="panel-title">
        <h1>Zgłoszenie rekrutacyjne</h1>
        <a href="{{ route('panel.applications.index') }}" class="btn btn-secondary btn-sm">← Wróć do listy</a>
    </div>

    <div class="card card-static mb-3" style="max-width:760px">
        <div class="card-body">
            <table class="table">
                <tbody>
                    <tr><th style="width:160px">Data</th><td>{{ $application->created_at?->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Imię i nazwisko</th><td>{{ $application->name }}</td></tr>
                    <tr><th>E-mail</th><td><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></td></tr>
                    <tr><th>Telefon</th><td>{{ $application->phone ?: '—' }}</td></tr>
                    <tr><th>Oferta</th><td>{{ $application->position?->title ?: 'aplikacja spontaniczna' }}</td></tr>
                    <tr>
                        <th>CV</th>
                        <td>
                            @if($application->cv_path)
                                <a href="{{ route('panel.applications.cv', $application) }}">
                                    {{ $application->cv_original_name ?: 'Pobierz CV' }}
                                </a>
                            @else
                                <span class="text-muted">brak załączonego CV</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="form-group" style="margin-top:18px">
                <label>List motywacyjny</label>
                <div style="white-space:pre-wrap;background:#fff;border:1px solid var(--line, #e2e8f0);border-radius:var(--radius, 10px);padding:14px">{{ $application->message ?: '—' }}</div>
            </div>

            <div style="margin:18px 0;padding:16px;background:#faf8f3;border:1px solid var(--line,#e2e8f0);border-radius:var(--radius,10px)">
                @php [$bg, $fg] = $application->statusColors(); @endphp
                <label style="display:block;font-weight:600;margin-bottom:8px">Status zgłoszenia:
                    <span class="badge" style="background:{{ $bg }};color:{{ $fg }};font-weight:600;margin-left:4px">{{ $application->statusLabel() }}</span>
                </label>
                <div class="d-flex gap-1" style="flex-wrap:wrap">
                    @foreach(\App\Modules\Storefront\Models\JobApplication::STATUSES as $key => $label)
                        <form method="POST" action="{{ route('panel.applications.status', $application) }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="status" value="{{ $key }}">
                            <button type="submit" class="btn btn-sm {{ $application->status === $key ? 'btn-primary' : 'btn-secondary' }}"
                                    {{ $application->status === $key ? 'disabled' : '' }}>{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-1">
                <a href="mailto:{{ $application->email }}?subject={{ rawurlencode('Re: aplikacja' . ($application->position ? ' — ' . $application->position->title : '')) }}" class="btn btn-primary">Odpowiedz e-mailem</a>
                @if($application->cv_path)
                    <a href="{{ route('panel.applications.cv', $application) }}" class="btn btn-secondary">Pobierz CV</a>
                @endif
                <form method="POST" action="{{ route('panel.applications.destroy', $application) }}"
                      onsubmit="return confirm('Usunąć zgłoszenie?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </form>
            </div>
        </div>
    </div>
@endsection
