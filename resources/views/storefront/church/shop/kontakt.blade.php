@extends('layouts.landing')

@section('title', 'Kontakt — ' . config('shop.name'))
@section('meta-description', 'Napisz do nas — odpowiemy najszybciej, jak to możliwe.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
@endpush

@section('content')
    {{-- SUB-HERO (spójnie z marką landingu) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <p class="sp-eyebrow">Jesteśmy do dyspozycji</p>
            <h1>Kontakt</h1>
            <p class="sp-lede">Masz pytanie lub chcesz aplikować na stanowisko? Napisz do nas — odpowiemy najszybciej, jak to możliwe.</p>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-formpage">
            <div class="sp-formpage__wrap">
                @if(session('success'))
                    <div class="sp-alert sp-alert--ok">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="sp-alert sp-alert--err">Popraw zaznaczone pola formularza.</div>
                @endif

                <div class="sp-card">
                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf

                        <div class="sp-field-row">
                            <div class="sp-field">
                                <label for="name">Imię i nazwisko *</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')<div class="sp-form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="sp-field">
                                <label for="email">Adres e-mail *</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')<div class="sp-form-error">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="sp-field-row">
                            <div class="sp-field">
                                <label for="phone">Numer telefonu</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')<div class="sp-form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="sp-field">
                                <label for="subject">Temat</label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject', $subject) }}">
                                @error('subject')<div class="sp-form-error">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="sp-field">
                            <label for="message">Wiadomość *</label>
                            <textarea id="message" name="message" required>{{ old('message') }}</textarea>
                            @error('message')<div class="sp-form-error">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="sp-btn sp-btn--block">Wyślij wiadomość</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
