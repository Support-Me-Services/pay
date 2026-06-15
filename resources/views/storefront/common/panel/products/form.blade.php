@extends('layouts.panel')

@section('title', $product->exists ? 'Edytuj produkt' : 'Dodaj produkt')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <style>
        #editor { background: #fff; min-height: 260px; }
        .ql-toolbar { border-radius: var(--radius) var(--radius) 0 0; background: #fff; }
        .ql-container { border-radius: 0 0 var(--radius) var(--radius); font-family: var(--font); font-size: 1rem; }
    </style>
@endpush

@section('content')
    <div class="panel-title">
        <h1>{{ $product->exists ? 'Edytuj: ' . $product->name : 'Dodaj produkt' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="productForm"
          action="{{ $product->exists ? route('panel.products.update', $product) : route('panel.products.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nazwa *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:160px">
                        <label for="price">Cena (zł) *</label>
                        <input type="text" id="price" name="price" inputmode="decimal"
                               value="{{ old('price', $product->exists ? number_format($product->price / 100, 2, ',', '') : '') }}" required>
                        @error('price')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:2;min-width:220px">
                        <label for="tag_uid">UID taga NFC *</label>
                        <input type="text" id="tag_uid" name="tag_uid" value="{{ old('tag_uid', $product->tag_uid) }}"
                               placeholder="np. TAG-S1-001" required>
                        @error('tag_uid')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="pickup_instruction">Instrukcja odbioru (pokazywana po opłaceniu)</label>
                    <textarea id="pickup_instruction" name="pickup_instruction" rows="3"
                              placeholder="np. Otwórz zamrażarkę i wyjmij jedno opakowanie...">{{ old('pickup_instruction', $product->pickup_instruction) }}</textarea>
                    @error('pickup_instruction')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Opis produktu (WYSIWYG)</label>
                    <div id="editor">{!! old('description_html', $product->description_html) !!}</div>
                    <textarea name="description_html" id="description_html" style="display:none">{{ old('description_html', $product->description_html) }}</textarea>
                    <div class="form-hint">Wstaw zdjęcie ikoną obrazka w pasku narzędzi — plik wgra się na serwer.</div>
                    @error('description_html')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="main_image">Zdjęcie główne {{ $product->main_image ? '(zostaw puste, by zachować obecne)' : '' }}</label>
                    @if($product->main_image)
                        <img src="{{ asset('storage/' . $product->main_image) }}" alt=""
                             style="width:96px;height:96px;object-fit:cover;border-radius:8px;margin-bottom:8px">
                    @endif
                    <input type="file" id="main_image" name="main_image" accept="image/*">
                    @error('main_image')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="gallery">Galeria (możesz wybrać wiele plików)</label>
                    @if($product->exists && $product->images->count())
                        <div class="d-flex gap-1 mb-1" style="flex-wrap:wrap">
                            @foreach($product->images as $image)
                                <div style="position:relative">
                                    <img src="{{ asset('storage/' . $image->path) }}" alt=""
                                         style="width:72px;height:72px;object-fit:cover;border-radius:8px">
                                    <button type="button" class="btn btn-danger btn-sm"
                                            style="position:absolute;top:-6px;right:-6px;padding:0 7px;border-radius:50%"
                                            onclick="deleteImage({{ $image->id }})">×</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                    @error('gallery.*')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  @checked(old('active', $product->exists ? $product->active : true))> aktywny</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.products.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>

    @if($product->exists)
        <form method="POST" id="deleteImageForm" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: [
                    [{header: [2, 3, false]}],
                    ['bold', 'italic', 'underline'],
                    [{list: 'ordered'}, {list: 'bullet'}],
                    ['link', 'image'],
                    ['clean'],
                ],
                handlers: {
                    image: function () {
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/*';
                        input.onchange = function () {
                            const file = input.files[0];
                            if (!file) return;
                            const fd = new FormData();
                            fd.append('image', file);
                            fetch(@json(route('panel.products.editor-upload')), {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': @json(csrf_token())},
                                body: fd,
                            })
                                .then(r => r.json())
                                .then(d => {
                                    const range = quill.getSelection(true);
                                    quill.insertEmbed(range.index, 'image', d.url);
                                })
                                .catch(() => alert('Nie udało się wgrać zdjęcia.'));
                        };
                        input.click();
                    }
                }
            }
        }
    });

    document.getElementById('productForm').addEventListener('submit', function () {
        document.getElementById('description_html').value = quill.root.innerHTML;
    });

    const deleteUrlTemplate = @json($product->exists ? route('panel.products.images.delete', [$product, '__ID__']) : '');

    function deleteImage(id) {
        if (!confirm('Usunąć zdjęcie z galerii?')) return;
        const form = document.getElementById('deleteImageForm');
        form.action = deleteUrlTemplate.replace('__ID__', id);
        form.submit();
    }
</script>
@endpush
