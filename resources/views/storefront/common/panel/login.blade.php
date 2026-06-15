<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie — panel · {{ config('shop.name') }}</title>
    {{-- Panel ZAWSZE używa theme.css (style admina: auth-card, form-group…),
         niezależnie od motywu publicznego hosta (church.css/theme.css). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ substr(md5_file(public_path('css/theme.css')), 0, 10) }}">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <h2 class="text-center">{{ config('shop.name') }}<span class="text-brand">.</span></h2>
            <p class="text-muted text-center mb-3">Panel sklepu</p>

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
</body>
</html>
