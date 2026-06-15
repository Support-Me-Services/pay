@extends('layouts.panel')

@section('title', $position->exists ? 'Edytuj stanowisko' : 'Dodaj stanowisko')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <style>
        #editor { background: #fff; min-height: 220px; }
        .ql-toolbar { border-radius: var(--radius) var(--radius) 0 0; background: #fff; }
        .ql-container { border-radius: 0 0 var(--radius) var(--radius); font-family: var(--font); font-size: 1rem; }
    </style>
@endpush

@section('content')
    <div class="panel-title">
        <h1>{{ $position->exists ? 'Edytuj: ' . $position->title : 'Dodaj stanowisko' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    <form method="POST" id="positionForm"
          action="{{ $position->exists ? route('panel.positions.update', $position) : route('panel.positions.store') }}">
        @csrf
        @if($position->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="title">Tytuł *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $position->title) }}" required>
                    @error('title')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="location">Lokalizacja</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $position->location) }}"
                               placeholder="np. Ełk / zdalnie">
                        @error('location')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="employment_type">Rodzaj zatrudnienia</label>
                        <input type="text" id="employment_type" name="employment_type"
                               value="{{ old('employment_type', $position->employment_type) }}"
                               placeholder="np. Pełny etat / Wolontariat">
                        @error('employment_type')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:0 0 120px">
                        <label for="sort">Kolejność</label>
                        <input type="number" id="sort" name="sort" min="0" max="65535"
                               value="{{ old('sort', $position->exists ? $position->sort : 0) }}">
                        @error('sort')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Opis stanowiska (WYSIWYG)</label>
                    <div id="editor">{!! old('description_html', $position->description_html) !!}</div>
                    <textarea name="description_html" id="description_html" style="display:none">{{ old('description_html', $position->description_html) }}</textarea>
                    @error('description_html')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  @checked(old('active', $position->exists ? $position->active : true))> aktywne</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.positions.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{header: [2, 3, false]}],
                ['bold', 'italic', 'underline'],
                [{list: 'ordered'}, {list: 'bullet'}],
                ['link'],
                ['clean'],
            ],
        }
    });

    document.getElementById('positionForm').addEventListener('submit', function () {
        document.getElementById('description_html').value = quill.root.innerHTML;
    });
</script>
@endpush
