@extends('layouts.panel')

@section('title', $shop->exists ? 'Edytuj sklep' : 'Dodaj sklep')

@section('content')
    <div class="panel-title">
        <h1>{{ $shop->exists ? 'Edytuj sklep: ' . $shop->name : 'Dodaj sklep' }}</h1>
    </div>

    <div class="card card-static" style="max-width:560px">
        <div class="card-body">
            <form method="POST" action="{{ $shop->exists ? route('panel.shops.update', $shop) : route('panel.shops.store') }}">
                @csrf
                @if($shop->exists) @method('PUT') @endif

                <div class="form-group">
                    <label for="name">Nazwa *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $shop->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="base_url">Adres URL sklepu *</label>
                    <input type="text" id="base_url" name="base_url" value="{{ old('base_url', $shop->base_url) }}" placeholder="https://please-support-me.com" required>
                    @error('base_url')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="payment_mode">Tryb płatności *</label>
                    <select id="payment_mode" name="payment_mode" required>
                        <option value="classic" @selected(old('payment_mode', $shop->payment_mode) === 'classic')>classic — przekierowanie na stronę płatności</option>
                        <option value="app2app" @selected(old('payment_mode', $shop->payment_mode) === 'app2app')>app2app — BLIK / aplikacja banku</option>
                    </select>
                    @error('payment_mode')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                @if(!$shop->exists)
                    <p class="form-hint mb-2">Klucz API zostanie wygenerowany automatycznie i pokazany jednorazowo po zapisaniu.</p>
                @endif

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.shops.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
@endsection
