@extends('layouts.public')

@section('title', 'Nieznana tabliczka')
@section('bare', true)

@section('content')
    <div class="status-screen plain">
        <div style="font-size:3.4rem;margin-bottom:10px">⛪</div>
        <h1 style="color:var(--navy)">Ta tabliczka nie jest<br>przypisana do parafii</h1>
        <p class="muted">Zapytaj obsługę kościoła albo wybierz parafię z listy.</p>
        <div class="actions">
            <a href="{{ route('category', 'miejsca-kultu') }}" class="btn btn-gold">Zobacz parafie</a>
        </div>
    </div>
@endsection
