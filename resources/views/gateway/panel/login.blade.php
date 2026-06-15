@extends('layouts.public')

@section('title', 'Logowanie — panel bramki')
@section('bare', true)

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h2 class="text-center">nfc<span class="text-brand">pay</span></h2>
            <p class="text-muted text-center mb-3">Panel bramki płatności</p>

            <form method="POST" action="{{ route('panel.login.post') }}">
                @csrf
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="password">Hasło</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Zaloguj się</button>
            </form>
        </div>
    </div>
@endsection
