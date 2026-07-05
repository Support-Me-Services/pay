@extends('layouts.panel')

@section('title', $item->exists ? 'Edytuj produkt' : 'Dodaj produkt')

@section('content')
    <div class="panel-title">
        <h1>{{ $item->exists ? 'Edytuj: ' . $item->name : 'Dodaj produkt' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $item->exists ? route('panel.shop-items.update', $item) : route('panel.shop-items.store') }}">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nazwa *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $item->name) }}" required placeholder="np. Serduszko">
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="description">Opis produktu</label>
                    <textarea id="description" name="description" rows="3" placeholder="Krótki opis oferty (widoczny w sklepie)">{{ old('description', $item->description) }}</textarea>
                    @error('description')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:220px">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $item->slug) }}" placeholder="puste = z nazwy">
                        @error('slug')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:0 0 160px">
                        <label for="price_pln">Cena (zł) *</label>
                        <input type="number" id="price_pln" name="price_pln" min="1" max="5000" required
                               value="{{ old('price_pln', $item->exists ? $item->pricePln() : 1) }}">
                        @error('price_pln')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:0 0 120px">
                        <label for="sort">Kolejność</label>
                        <input type="number" id="sort" name="sort" min="0" max="65535"
                               value="{{ old('sort', $item->exists ? $item->sort : 0) }}">
                        @error('sort')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="tag_uid">Tag NFC (UID) — kieruje na stronę sklepu z tym produktem</label>
                    <input type="text" id="tag_uid" name="tag_uid" value="{{ old('tag_uid', $item->tag_uid) }}" placeholder="np. 04A1B2C3D4 (opcjonalne)">
                    @error('tag_uid')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="image_file">Grafika produktu (jpg/png/svg, do 5 MB)</label>
                    @if($item->image)
                        <div style="margin-bottom:8px">
                            <img src="{{ asset($item->image) }}" alt="" style="width:80px;height:80px;object-fit:contain;border-radius:8px;background:#f6f9fb">
                        </div>
                    @endif
                    <input type="file" id="image_file" name="image_file" accept="image/*">
                    @error('image_file')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="is_default" value="1" style="width:auto"
                                  @checked(old('is_default', $item->is_default))> domyślny produkt (pokazywany w modalu po wejściu na sklep)</label>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  @checked(old('active', $item->exists ? $item->active : true))> aktywny (widoczny w sklepie)</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.shop-items.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>
@endsection
