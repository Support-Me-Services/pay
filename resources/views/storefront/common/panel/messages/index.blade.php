@extends('layouts.panel')

@section('title', 'Wiadomości')

@section('content')
    <div class="panel-title">
        <h1>Wiadomości
            @if($unread > 0)
                <span class="badge badge-brand">{{ $unread }} nowych</span>
            @endif
        </h1>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th><th>Nadawca</th><th>Temat</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($messages as $message)
                        <tr style="{{ $message->is_read ? '' : 'font-weight:700' }}">
                            <td class="nowrap">{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $message->name }}<br>
                                <span class="text-muted" style="font-weight:400">{{ $message->email }}</span>
                            </td>
                            <td>{{ $message->subject ?: '—' }}</td>
                            <td>
                                @if($message->is_read)
                                    <span class="badge badge-muted">przeczytane</span>
                                @else
                                    <span class="badge badge-success">nowe</span>
                                @endif
                            </td>
                            <td class="actions nowrap">
                                <a href="{{ route('panel.messages.show', $message) }}">Otwórz</a>
                                <form method="POST" action="{{ route('panel.messages.destroy', $message) }}" style="display:inline"
                                      onsubmit="return confirm('Usunąć wiadomość?');">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Brak wiadomości.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
