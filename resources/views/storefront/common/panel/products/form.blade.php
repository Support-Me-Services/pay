@extends('layouts.panel')

@section('title', $product->exists ? 'Edytuj parafię' : 'Dodaj parafię')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <style>
        #editor { background: #fff; min-height: 260px; }
        .ql-toolbar { border-radius: var(--radius) var(--radius) 0 0; background: #fff; }
        .ql-container { border-radius: 0 0 var(--radius) var(--radius); font-family: var(--font); font-size: 1rem; }
        .note-item { border:1px solid var(--line,#e2e8f0); border-radius:8px; padding:10px 12px; margin-bottom:8px; background:#fff; }
        .note-meta { font-size:.82rem; color:#64748b; margin-bottom:4px; }
    </style>
@endpush

@section('content')
    <div class="panel-title">
        <h1>{{ $product->exists ? 'Edytuj: ' . $product->name : 'Dodaj parafię' }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    @endif

    {{-- Pipeline statusów — szybkie przejście kontakt → test → wdrożenie → aktywna --}}
    @if($product->exists)
        @php [$bg, $fg] = $product->statusColors(); @endphp
        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="d-flex gap-1" style="align-items:center;flex-wrap:wrap">
                    <span>Status:</span>
                    <span class="badge" style="background:{{ $bg }};color:{{ $fg }};font-weight:600">{{ $product->statusLabel() }}</span>
                    <span class="text-muted" style="margin:0 6px">→ przenieś do:</span>
                    @foreach(\App\Modules\Storefront\Models\Product::STATUSES as $key => $label)
                        @if($key !== $product->status)
                            <form method="POST" action="{{ route('panel.parishes.status', $product) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ $key }}">
                                <button type="submit" class="btn btn-secondary btn-sm">{{ $label }}</button>
                            </form>
                        @endif
                    @endforeach
                </div>
                <div class="form-hint" style="margin-top:6px">Status „Aktywna" publikuje parafię na stronie; pozostałe ją ukrywają (lead).</div>
            </div>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="productForm"
          action="{{ $product->exists ? route('panel.products.update', $product) : route('panel.products.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nazwa parafii *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="city">Miasto</label>
                        <input type="text" id="city" name="city" value="{{ old('city', $product->city) }}" placeholder="np. Kraków">
                        @error('city')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="voivodeship">Województwo</label>
                        <select id="voivodeship" name="voivodeship">
                            <option value="">— wybierz —</option>
                            @foreach(\App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS as $voiv)
                                <option value="{{ $voiv }}" @selected(old('voivodeship', $product->voivodeship) === $voiv)>{{ $voiv }}</option>
                            @endforeach
                        </select>
                        @error('voivodeship')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $product->phone) }}" placeholder="np. 600 100 200">
                        @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="website">Strona www</label>
                        <input type="text" id="website" name="website" value="{{ old('website', $product->website) }}" placeholder="np. parafia.pl">
                        @error('website')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            @foreach(\App\Modules\Storefront\Models\Product::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $product->status ?? 'kontakt') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="salesperson_id">Handlowiec</label>
                        <select id="salesperson_id" name="salesperson_id">
                            <option value="">— brak —</option>
                            @foreach($salespeople as $sp)
                                <option value="{{ $sp->id }}" @selected((int) old('salesperson_id', $product->salesperson_id) === $sp->id)>{{ $sp->name }}</option>
                            @endforeach
                        </select>
                        @error('salesperson_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:160px">
                        <label for="price">Kwota wpłaty (zł) *</label>
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
                    <label for="pickup_instruction">Instrukcja po wpłacie (pokazywana po opłaceniu)</label>
                    <textarea id="pickup_instruction" name="pickup_instruction" rows="3"
                              placeholder="np. Bóg zapłać za wsparcie parafii…">{{ old('pickup_instruction', $product->pickup_instruction) }}</textarea>
                    @error('pickup_instruction')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Opis parafii (WYSIWYG)</label>
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

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="{{ route('panel.products.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>

    {{-- ====== Panel notatek CRM (AJAX) ====== --}}
    @if($product->exists)
        <div class="card card-static mb-3" style="max-width:840px" id="crmNotes"
             data-store-url="{{ route('panel.parishes.notes.store', $product) }}"
             data-destroy-url="{{ route('panel.parishes.notes.destroy', [$product, '__ID__']) }}">
            <div class="card-body">
                <h2 style="margin-top:0">Notatki CRM</h2>
                <div class="form-hint">Historia kontaktu — kiedy i co się wydarzyło.</div>

                <form id="noteForm" class="d-flex gap-2 mb-3" style="flex-wrap:wrap;align-items:flex-end">
                    <div class="form-group" style="flex:0 0 160px;margin-bottom:0">
                        <label for="note_type">Typ</label>
                        <select id="note_type" name="type">
                            @foreach(\App\Modules\Storefront\Models\ParishNote::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;min-width:240px;margin-bottom:0">
                        <label for="note_body">Treść</label>
                        <textarea id="note_body" name="body" rows="2" placeholder="np. Rozmowa z ks. proboszczem, umówione spotkanie…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Dodaj notatkę</button>
                </form>
                <div id="noteError" class="form-error" style="display:none"></div>

                <div id="noteList">
                    @forelse($product->notes as $note)
                        <div class="note-item" data-id="{{ $note->id }}">
                            <div class="note-meta">
                                <strong>{{ $note->typeLabel() }}</strong> · {{ $note->created_at?->format('Y-m-d H:i') }}
                                @if($note->author) · {{ $note->author }} @endif
                                <a href="#" class="note-del" style="float:right;color:#b23b3b">Usuń</a>
                            </div>
                            <div>{{ $note->body }}</div>
                        </div>
                    @empty
                        <div class="text-muted" id="noteEmpty">Brak notatek.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

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

    // ====== Notatki CRM (AJAX) ======
    (function () {
        const root = document.getElementById('crmNotes');
        if (!root) return;
        const csrf = @json(csrf_token());
        const storeUrl = root.dataset.storeUrl;
        const destroyTpl = root.dataset.destroyUrl;
        const list = document.getElementById('noteList');
        const errBox = document.getElementById('noteError');

        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : s;
            return d.innerHTML;
        }

        function renderNote(n) {
            const wrap = document.createElement('div');
            wrap.className = 'note-item';
            wrap.dataset.id = n.id;
            wrap.innerHTML =
                '<div class="note-meta"><strong>' + esc(n.type_label) + '</strong> · ' + esc(n.created_at) +
                (n.author ? ' · ' + esc(n.author) : '') +
                '<a href="#" class="note-del" style="float:right;color:#b23b3b">Usuń</a></div>' +
                '<div>' + esc(n.body) + '</div>';
            return wrap;
        }

        document.getElementById('noteForm').addEventListener('submit', function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            const body = document.getElementById('note_body').value.trim();
            const type = document.getElementById('note_type').value;
            if (!body) { errBox.textContent = 'Wpisz treść notatki.'; errBox.style.display = 'block'; return; }

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({body: body, type: type}),
            })
                .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
                .then(n => {
                    const empty = document.getElementById('noteEmpty');
                    if (empty) empty.remove();
                    list.insertBefore(renderNote(n), list.firstChild);
                    document.getElementById('note_body').value = '';
                })
                .catch(() => { errBox.textContent = 'Nie udało się zapisać notatki.'; errBox.style.display = 'block'; });
        });

        list.addEventListener('click', function (e) {
            const del = e.target.closest('.note-del');
            if (!del) return;
            e.preventDefault();
            const item = del.closest('.note-item');
            const id = item.dataset.id;
            if (!confirm('Usunąć notatkę?')) return;
            fetch(destroyTpl.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'DELETE',
                },
            })
                .then(r => { if (r.ok) item.remove(); });
        });
    })();
</script>
@endpush
