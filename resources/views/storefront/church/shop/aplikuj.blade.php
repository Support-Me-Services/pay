@extends('layouts.public')

@section('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'))
@section('meta-description', 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.')

@push('head')
<style>
    .apply-hero { text-align: center; padding: 56px 20px 24px; }
    .apply-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .apply-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(1.8rem, 5vw, 2.6rem); color: var(--navy); margin: 10px 0 12px; }
    .apply-hero .lede { color: var(--ink-soft); max-width: 560px; margin: 0 auto; font-size: 1.05rem; }

    .apply-wrap { max-width: 620px; margin: 0 auto; padding: 0 18px 64px; }
    .apply-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow); padding: 28px 26px; }

    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: .88rem; font-weight: 600; color: var(--ink-soft); margin-bottom: 7px; }
    .field input, .field textarea { width: 100%; box-sizing: border-box; font-family: var(--ui); font-size: 1rem;
        color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 12px 14px; }
    .field input[type=file] { padding: 10px 12px; background: #fbf7ee; }
    .field input:focus, .field textarea:focus { outline: none; border-color: var(--gold); box-shadow: var(--shadow-gold); }
    .field textarea { resize: vertical; min-height: 130px; }
    .field .hint { font-size: .78rem; color: var(--muted); margin-top: 6px; }
    .field-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .field-row .field { flex: 1; min-width: 200px; }
    .form-error { color: var(--error); font-size: .82rem; margin-top: 6px; }

    .alert-success { background: #e8f4ed; border: 1px solid #aed5c1; color: #1f5f44; }
</style>
@endpush

@section('content')
    <section class="apply-hero">
        <div class="eyebrow">Aplikuj</div>
        <h1>{{ $position ? $position->title : 'Aplikacja spontaniczna' }}</h1>
        <p class="lede">
            @if($position)
                Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.
            @else
                Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a skontaktujemy się, gdy pojawi się odpowiednie stanowisko.
            @endif
        </p>
    </section>

    <div class="apply-wrap">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
        @endif

        <div class="apply-card">
            <form method="POST"
                  action="{{ $position ? route('careers.apply.store', $position) : route('careers.apply.general.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="field-row">
                    <div class="field">
                        <label for="name">Imię i nazwisko *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="field">
                    <label for="phone">Telefon</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="message">List motywacyjny</label>
                    <textarea id="message" name="message">{{ old('message') }}</textarea>
                    @error('message')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="cv">CV {{ $position ? '*' : '' }}</label>
                    <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" {{ $position ? 'required' : '' }}>
                    <div class="hint">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</div>
                    @error('cv')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-gold btn-block">Wyślij zgłoszenie</button>
            </form>
        </div>

        <p style="text-align:center;margin-top:18px">
            <a href="{{ route('careers') }}" style="color:var(--gold-deep)">← Wróć do ofert</a>
        </p>
    </div>
@endsection
