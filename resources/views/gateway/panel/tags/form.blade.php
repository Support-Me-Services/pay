@extends('layouts.panel')

@section('title', ($tag->exists ? 'Edytuj tag' : 'Dodaj tag') . ' — ' . $shop->name)

@section('content')
    <div class="panel-title">
        <h1>{{ $tag->exists ? 'Edytuj tag: ' . $tag->tag_uid : 'Dodaj tag — ' . $shop->name }}</h1>
    </div>

    <div class="card card-static" style="max-width:560px">
        <div class="card-body">
            <form method="POST" action="{{ $tag->exists ? route('panel.tags.update', [$shop, $tag]) : route('panel.tags.store', $shop) }}">
                @csrf
                @if($tag->exists) @method('PUT') @endif

                <div class="form-group">
                    <label for="tag_uid">UID taga *</label>
                    <input type="text" id="tag_uid" name="tag_uid" value="{{ old('tag_uid', $tag->tag_uid) }}" placeholder="np. TAG-S1-001 lub UID NTAG" required>
                    @error('tag_uid')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="label">Etykieta</label>
                    <input type="text" id="label" name="label" value="{{ old('label', $tag->label) }}" placeholder="np. Zamrażarka — lody pistacjowe">
                    @error('label')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="target_url">Docelowy URL *</label>
                    <input type="text" id="target_url" name="target_url" value="{{ old('target_url', $tag->target_url) }}" placeholder="{{ $shop->base_url }}/t/TAG-S1-001" required>
                    <div class="form-hint">Adres, do którego prowadzi tag — zapisz go na tagu NFC (rekord NDEF typu URL).</div>
                    @error('target_url')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto" @checked(old('active', $tag->exists ? $tag->active : true))> aktywny</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.tags.index', $shop) }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
@endsection
