@extends('layouts.public')

@section('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'))

@section('content')
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:620px">
            <h1 style="font-size:1.7rem">{{ $position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna' }}</h1>
            <p class="text-muted mb-3">
                @if($position)
                    Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.
                @else
                    Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a odezwiemy się, gdy pojawi się odpowiednie stanowisko.
                @endif
            </p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
            @endif

            <div class="card" style="padding:24px">
                <form method="POST"
                      action="{{ $position ? route('careers.apply.store', $position) : route('careers.apply.general.store') }}"
                      enctype="multipart/form-data">
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
                        <label for="message">List motywacyjny</label>
                        <textarea id="message" name="message" rows="6">{{ old('message') }}</textarea>
                        @error('message')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="cv">CV {{ $position ? '*' : '' }}</label>
                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" {{ $position ? 'required' : '' }}>
                        <small class="text-muted">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</small>
                        @error('cv')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Wyślij zgłoszenie</button>
                    <a href="{{ route('careers') }}" class="btn btn-secondary">Wróć do ofert</a>
                </form>
            </div>
        </div>
    </section>
@endsection
