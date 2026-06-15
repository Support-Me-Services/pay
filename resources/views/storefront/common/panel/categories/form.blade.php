@extends('layouts.panel')

@section('title', $category->exists ? 'Edytuj kategorię' : 'Dodaj kategorię')

@section('content')
    <div class="panel-title">
        <h1>{{ $category->exists ? 'Edytuj: ' . $category->label_text : 'Dodaj kategorię' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $category->exists ? route('panel.categories.update', $category) : route('panel.categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="parent_id">Kategoria nadrzędna</label>
                    <select id="parent_id" name="parent_id">
                        <option value="">— (kategoria główna)</option>
                        @foreach($parents as $id => $optLabel)
                            <option value="{{ $id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $id)>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="label">Nazwa *</label>
                    <input type="text" id="label" name="label" value="{{ old('label', $category->label) }}" required>
                    @error('label')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:240px">
                        <label for="label_text">Nazwa na stronie kategorii (tekst)</label>
                        <input type="text" id="label_text" name="label_text" value="{{ old('label_text', $category->label_text) }}">
                        <div class="form-hint">Gdy puste — użyje „Nazwa".</div>
                        @error('label_text')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:240px">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}">
                        <div class="form-hint">Gdy puste — wygeneruje z nazwy.</div>
                        @error('slug')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="label_html">Nazwa na kafelku (HTML)</label>
                    <textarea id="label_html" name="label_html" rows="2">{{ old('label_html', $category->label_html) }}</textarea>
                    <div class="form-hint">Można użyć <code>&lt;br&gt;</code> do łamania wiersza. Gdy puste — użyje nazwy tekstowej.</div>
                    @error('label_html')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="intro">Opis (intro strony kategorii)</label>
                    <textarea id="intro" name="intro" rows="3">{{ old('intro', $category->intro) }}</textarea>
                    @error('intro')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="source">Źródło pozycji</label>
                        <select id="source" name="source">
                            @foreach(\App\Modules\Storefront\Models\Category::SOURCES as $key => $optLabel)
                                <option value="{{ $key }}" @selected(old('source', $category->source ?? 'none') === $key)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                        @error('source')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="position">Kolejność</label>
                        <input type="number" id="position" name="position" min="0" value="{{ old('position', $category->position ?? 0) }}">
                        @error('position')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="icon">Ikonka</label>
                    @if($category->icon)
                        <div style="margin-bottom:8px">
                            <img src="{{ asset('storage/' . $category->icon) }}" alt=""
                                 style="width:64px;height:64px;border-radius:50%;object-fit:cover">
                        </div>
                    @endif
                    <input type="file" id="icon" name="icon" accept="image/*">
                    <div class="form-hint">PNG/JPG/SVG. Pozostaw puste, aby zachować obecną.</div>
                    @error('icon')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  @checked(old('active', $category->exists ? $category->active : true))> aktywna (widoczna na stronie)</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.categories.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>
@endsection
