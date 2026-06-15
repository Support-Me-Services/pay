@extends('layouts.panel')

@section('title', $salesperson->exists ? 'Edytuj handlowca' : 'Dodaj handlowca')

@section('content')
    <div class="panel-title">
        <h1>{{ $salesperson->exists ? 'Edytuj: ' . $salesperson->name : 'Dodaj handlowca' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    @php
        $selectedVoiv = old('voivodeships', $salesperson->voivodeships ?? []);
        $selectedVoiv = is_array($selectedVoiv) ? $selectedVoiv : [];
    @endphp

    <form method="POST"
          action="{{ $salesperson->exists ? route('panel.salespeople.update', $salesperson) : route('panel.salespeople.store') }}">
        @csrf
        @if($salesperson->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Imię i nazwisko *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $salesperson->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $salesperson->email) }}">
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $salesperson->phone) }}">
                        @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Obsługiwane województwa</label>
                    <div class="d-flex gap-1" style="flex-wrap:wrap">
                        @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $voiv)
                            <label style="flex:0 0 220px;font-weight:400">
                                <input type="checkbox" name="voivodeships[]" value="{{ $voiv }}" style="width:auto"
                                       @checked(in_array($voiv, $selectedVoiv, true))> {{ $voiv }}
                            </label>
                        @endforeach
                    </div>
                    @error('voivodeships')<div class="form-error">{{ $message }}</div>@enderror
                    @error('voivodeships.*')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  @checked(old('active', $salesperson->exists ? $salesperson->active : true))> aktywny</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.salespeople.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Lista parafii przypisanych do handlowca --}}
    @if($salesperson->exists && $salesperson->parishes->count())
        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <h2 style="margin-top:0">Parafie tego handlowca ({{ $salesperson->parishes->count() }})</h2>
                <ul style="margin:0;padding-left:18px">
                    @foreach($salesperson->parishes as $parish)
                        <li><a href="{{ route('panel.products.edit', $parish) }}">{{ $parish->name }}</a>
                            @if($parish->city) <span class="text-muted">— {{ $parish->city }}</span> @endif
                        </li>
                    @endforeach
                </ul>
                <div class="form-hint" style="margin-top:8px">
                    <a href="{{ route('panel.products.index', ['q' => $salesperson->name]) }}">Pokaż w liście parafii →</a>
                </div>
            </div>
        </div>
    @endif
@endsection
