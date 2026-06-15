@extends('layouts.landing')

@section('title', 'Praca — ' . config('shop.name'))
@section('meta-description', 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.')

@push('head')
<link rel="stylesheet" href="{{ asset('css/subpages.css') }}?v={{ filemtime(public_path('css/subpages.css')) }}">
@endpush

@section('content')
    {{-- SUB-HERO „Pracuj z nami!" (1:1 wg Figmy REKRUTACJA) --}}
    <section class="sp-subhero">
        <div class="sp-subhero__inner">
            <h1>Pracuj z nami!</h1>
        </div>
    </section>

    <div class="sp-wrap">
        <div class="sp-container sp-careers">
            {{-- Nagłówek z dużym kołem „grafika" + treścią rekrutacji (wg Figmy) --}}
            <div class="sp-careers__head">
                <div class="sp-circle sp-circle--lg">grafika</div>
                <div class="sp-careers__head-body">
                    <h2>Tworzymy coś dobrego — razem</h2>
                    <p>Szukamy osób, które chcą łączyć technologię z realnym wpływem na świat. Rozwiń ofertę i wyślij zgłoszenie wraz z CV — odezwiemy się.</p>
                </div>
            </div>

            <div class="sp-jobs">
                @forelse($positions as $position)
                    @php $isOpen = old('apply_position') == $position->id || session('apply_done') === (string) $position->id; @endphp
                    <article class="sp-job" id="oferta-{{ $position->id }}">
                        <h2>{{ $position->title }}</h2>
                        @if($position->location || $position->employment_type)
                            <div class="sp-job__chips">
                                @if($position->location)<span class="sp-job__chip">📍 {{ $position->location }}</span>@endif
                                @if($position->employment_type)<span class="sp-job__chip">🕒 {{ $position->employment_type }}</span>@endif
                            </div>
                        @endif
                        @if($position->description_html)
                            <div class="sp-job__desc">{!! $position->description_html !!}</div>
                        @endif

                        @if(session('apply_done') === (string) $position->id && session('success'))
                            <div class="sp-alert sp-alert--ok">{{ session('success') }}</div>
                        @endif

                        {{-- FORMULARZ REKRUTACYJNY wbudowany w ofertę --}}
                        <details class="sp-apply" {{ $isOpen ? 'open' : '' }}>
                            <summary><span class="sp-btn">Aplikuj na to stanowisko</span></summary>
                            <div class="sp-apply__inner">
                                @if(old('apply_position') == $position->id && $errors->any())
                                    <div class="sp-alert sp-alert--err">Popraw zaznaczone pola i wyślij ponownie.</div>
                                @endif
                                <form method="POST" action="{{ route('careers.apply.store', $position) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="apply_position" value="{{ $position->id }}">
                                    <div class="sp-field-row">
                                        <div class="sp-field">
                                            <label>Imię i nazwisko *</label>
                                            <input type="text" name="name" value="{{ old('apply_position') == $position->id ? old('name') : '' }}" required>
                                            @if(old('apply_position') == $position->id)@error('name')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                        </div>
                                        <div class="sp-field">
                                            <label>Adres e-mail *</label>
                                            <input type="email" name="email" value="{{ old('apply_position') == $position->id ? old('email') : '' }}" required>
                                            @if(old('apply_position') == $position->id)@error('email')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                        </div>
                                    </div>
                                    <div class="sp-field">
                                        <label>Numer telefonu</label>
                                        <input type="text" name="phone" value="{{ old('apply_position') == $position->id ? old('phone') : '' }}">
                                    </div>
                                    <div class="sp-field">
                                        <label>List motywacyjny</label>
                                        <textarea name="message">{{ old('apply_position') == $position->id ? old('message') : '' }}</textarea>
                                    </div>
                                    <div class="sp-field">
                                        <label>Dodaj CV *</label>
                                        <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
                                        <div class="sp-hint">PDF, DOC lub DOCX, maks. 5 MB.</div>
                                        @if(old('apply_position') == $position->id)@error('cv')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                    </div>
                                    <div class="sp-consent">
                                        <input type="checkbox" id="rodo-{{ $position->id }}" required>
                                        <label for="rodo-{{ $position->id }}">Wyrażam zgodę na przetwarzanie moich danych osobowych w celu prowadzenia rekrutacji (RODO).</label>
                                    </div>
                                    <button type="submit" class="sp-btn sp-btn--block">Wyślij zgłoszenie</button>
                                </form>
                            </div>
                        </details>
                    </article>
                @empty
                    <p class="sp-job--empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>
                @endforelse

                {{-- Aplikacja spontaniczna (bez konkretnej oferty) --}}
                @php $spontOpen = old('apply_position') === 'spontaniczna' || session('apply_done') === 'general'; @endphp
                <article class="sp-job" id="oferta-spontaniczna">
                    <h2>Nie znalazłeś oferty dla siebie?</h2>
                    <p class="sp-job__desc">Zostaw nam swoje zgłoszenie — odezwiemy się, gdy pojawi się odpowiednie stanowisko.</p>
                    @if(session('apply_done') === 'general' && session('success'))
                        <div class="sp-alert sp-alert--ok">{{ session('success') }}</div>
                    @endif
                    <details class="sp-apply" {{ $spontOpen ? 'open' : '' }}>
                        <summary><span class="sp-btn sp-btn--ghost">Aplikuj spontanicznie</span></summary>
                        <div class="sp-apply__inner">
                            @if(old('apply_position') === 'spontaniczna' && $errors->any())
                                <div class="sp-alert sp-alert--err">Popraw zaznaczone pola i wyślij ponownie.</div>
                            @endif
                            <form method="POST" action="{{ route('careers.apply.general.store') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="apply_position" value="spontaniczna">
                                <div class="sp-field-row">
                                    <div class="sp-field">
                                        <label>Imię i nazwisko *</label>
                                        <input type="text" name="name" value="{{ old('apply_position') === 'spontaniczna' ? old('name') : '' }}" required>
                                        @if(old('apply_position') === 'spontaniczna')@error('name')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                    </div>
                                    <div class="sp-field">
                                        <label>Adres e-mail *</label>
                                        <input type="email" name="email" value="{{ old('apply_position') === 'spontaniczna' ? old('email') : '' }}" required>
                                        @if(old('apply_position') === 'spontaniczna')@error('email')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                    </div>
                                </div>
                                <div class="sp-field">
                                    <label>Numer telefonu</label>
                                    <input type="text" name="phone" value="{{ old('apply_position') === 'spontaniczna' ? old('phone') : '' }}">
                                </div>
                                <div class="sp-field">
                                    <label>List motywacyjny</label>
                                    <textarea name="message">{{ old('apply_position') === 'spontaniczna' ? old('message') : '' }}</textarea>
                                </div>
                                <div class="sp-field">
                                    <label>Dodaj CV</label>
                                    <input type="file" name="cv" accept=".pdf,.doc,.docx">
                                    <div class="sp-hint">PDF, DOC lub DOCX, maks. 5 MB (opcjonalnie).</div>
                                    @if(old('apply_position') === 'spontaniczna')@error('cv')<div class="sp-form-error">{{ $message }}</div>@enderror @endif
                                </div>
                                <div class="sp-consent">
                                    <input type="checkbox" id="rodo-spontaniczna" required>
                                    <label for="rodo-spontaniczna">Wyrażam zgodę na przetwarzanie moich danych osobowych w celu prowadzenia rekrutacji (RODO).</label>
                                </div>
                                <button type="submit" class="sp-btn sp-btn--ghost sp-btn--block">Wyślij zgłoszenie</button>
                            </form>
                        </div>
                    </details>
                </article>
            </div>
        </div>
    </div>
@endsection
