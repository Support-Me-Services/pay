@extends('layouts.public')

@section('title', 'Kontakt — ' . config('shop.name'))

@section('content')
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:620px">
            <h1 style="font-size:1.7rem">Kontakt</h1>
            <p class="text-muted mb-3">Napisz do nas — odpowiemy najszybciej, jak to możliwe.</p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
            @endif

            <div class="card" style="padding:24px">
                <form method="POST" action="{{ route('contact.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="name">Imię i nazwisko *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="subject">Temat</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject', $subject) }}">
                        @error('subject')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="message">Wiadomość *</label>
                        <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                        @error('message')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Wyślij wiadomość</button>
                </form>
            </div>
        </div>
    </section>
@endsection
