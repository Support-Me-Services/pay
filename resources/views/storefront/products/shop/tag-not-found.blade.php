@extends('layouts.public')

@section('title', 'Nieznany tag')
@section('bare', true)

@section('content')
    <div class="fullscreen-status" style="background:var(--bg-alt);color:var(--ink)">
        <div style="font-size:4rem;margin-bottom:12px">🏷️</div>
        <h1 style="color:var(--ink);font-size:1.6rem">Ten tag nie jest przypisany<br>do żadnego produktu</h1>
        <p class="text-muted">Zapytaj obsługę punktu lub wybierz produkt z listy.</p>
        <a href="{{ route('home') }}" class="btn btn-primary mt-2">Zobacz produkty</a>
    </div>
@endsection
