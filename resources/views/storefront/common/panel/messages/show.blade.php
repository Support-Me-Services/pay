@extends('layouts.panel')

@section('title', 'Wiadomość')

@section('content')
    <div class="panel-title">
        <h1>Wiadomość</h1>
        <a href="{{ route('panel.messages.index') }}" class="btn btn-secondary btn-sm">← Wróć do listy</a>
    </div>

    <div class="card card-static mb-3" style="max-width:760px">
        <div class="card-body">
            <table class="table">
                <tbody>
                    <tr><th style="width:160px">Data</th><td>{{ $message->created_at?->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Imię i nazwisko</th><td>{{ $message->name }}</td></tr>
                    <tr><th>E-mail</th><td><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></td></tr>
                    <tr><th>Telefon</th><td>{{ $message->phone ?: '—' }}</td></tr>
                    <tr><th>Temat</th><td>{{ $message->subject ?: '—' }}</td></tr>
                </tbody>
            </table>

            <div class="form-group" style="margin-top:18px">
                <label>Wiadomość</label>
                <div style="white-space:pre-wrap;background:#fff;border:1px solid var(--line, #e2e8f0);border-radius:var(--radius, 10px);padding:14px">{{ $message->message }}</div>
            </div>

            <div class="d-flex gap-1">
                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . ($message->subject ?: 'Twoja wiadomość')) }}" class="btn btn-primary">Odpowiedz e-mailem</a>
                <form method="POST" action="{{ route('panel.messages.destroy', $message) }}"
                      onsubmit="return confirm('Usunąć wiadomość?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </form>
            </div>
        </div>
    </div>
@endsection
