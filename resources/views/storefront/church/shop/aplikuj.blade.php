@extends('layouts.landing')

@section('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'))
@section('meta-description', 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ filemtime(public_path('css/subpages.css')) }}">
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
                    <div class="sp-alert sp-alert--ok">{{ session('success') }}</div>
                @endif

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

                <p class="sp-formpage__foot">
                    <a href="{{ route('careers') }}" class="sp-link">← Wróć do ofert</a>
                </p>
            </div>
        </div>
    </div>
@endsection
