@extends('layouts.landing')

@section('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'))
@section('meta-description', 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ substr(md5_file(public_path('css/subpages.css')), 0, 10) }}">
<style>
    .sp-thanks{ text-align:center; padding:48px 28px; }
    .sp-thanks__icon{ width:72px; height:72px; margin:0 auto 18px; border-radius:50%;
        display:flex; align-items:center; justify-content:center; font-size:38px; color:#fff;
        background:linear-gradient(117deg,#4E7FA7,#1473C0); box-shadow:0 12px 30px rgba(20,115,192,.3); }
    .sp-thanks h2{ font-family:'Libre Baskerville',serif; color:#24324A; margin:0 0 10px; font-size:28px; }
    .sp-thanks p{ color:#3a3d47; font-size:18px; margin:0 0 24px; }
    .sp-btn--inline{ display:inline-flex; width:auto; padding:14px 30px; }
</style>
@endpush

@section('content')
    {{-- SUB-HERO „Pracuj z nami!" (spójnie z Figmą REKRUTACJA) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <a href="{{ route('careers') }}" class="sp-back">← wróć do ofert</a>
            <h1>{{ $position ? $position->title : 'Pracuj z nami!' }}</h1>
            <p class="sp-lede">
                @if($position)
                    Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.
                @else
                    Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a skontaktujemy się, gdy pojawi się odpowiednie stanowisko.
                @endif
            </p>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-formpage">
            <div class="sp-formpage__wrap">
                @if(session('success'))
                    <div class="sp-card sp-thanks">
                        <div class="sp-thanks__icon" aria-hidden="true">✓</div>
                        <h2>Zgłoszenie wysłane!</h2>
                        <p>{{ session('success') }}</p>
                        <a href="{{ route('careers') }}" class="sp-btn sp-btn--inline">Wróć do ofert</a>
                    </div>
                @else
                    @if($errors->any())
                        <div class="sp-alert sp-alert--err">Popraw zaznaczone pola formularza.</div>
                    @endif

                    <div class="sp-card">
                    <form method="POST"
                          action="{{ $position ? route('careers.apply.store', $position) : route('careers.apply.general.store') }}"
                          enctype="multipart/form-data">
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

                        <div class="sp-field">
                            <label for="phone">Numer telefonu</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')<div class="sp-form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="sp-field">
                            <label for="message">List motywacyjny</label>
                            <textarea id="message" name="message">{{ old('message') }}</textarea>
                            @error('message')<div class="sp-form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="sp-field">
                            <label for="cv">Dodaj CV {{ $position ? '*' : '' }}</label>
                            <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" {{ $position ? 'required' : '' }}>
                            <div class="sp-hint">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</div>
                            @error('cv')<div class="sp-form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="sp-consent">
                            <input type="checkbox" id="rodo" required>
                            <label for="rodo">Wyrażam zgodę na przetwarzanie moich danych osobowych w celu prowadzenia rekrutacji (RODO).</label>
                        </div>

                        <button type="submit" class="sp-btn sp-btn--block">Wyślij zgłoszenie</button>
                    </form>
                    </div>
                @endif

                <p class="sp-formpage__foot">
                    <a href="{{ route('careers') }}" class="sp-link">← Wróć do ofert</a>
                </p>
            </div>
        </div>
    </div>
@endsection
