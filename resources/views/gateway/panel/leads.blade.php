@extends('layouts.panel')

@section('title', 'Leady')

@section('content')
    <div class="panel-title">
        <h1>Leady z Landing Page</h1>
        <a href="{{ route('panel.leads.export') }}" class="btn btn-primary btn-sm">Eksport CSV</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th><th>Imię i nazwisko</th><th>E-mail</th><th>Telefon</th><th>Firma</th><th>Wiadomość</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="nowrap">{{ $lead->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="fw-bold">{{ $lead->name }}</td>
                            <td><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></td>
                            <td><a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a></td>
                            <td>{{ $lead->company ?: '—' }}</td>
                            <td style="max-width:340px">{{ $lead->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Brak zgłoszeń.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $leads->links() }}</div>
        </div>
    </div>
@endsection
