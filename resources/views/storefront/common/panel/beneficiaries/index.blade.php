@extends('layouts.panel')

@section('title', 'Wspieramy — edytor podstrony')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<style>
    .bn-list{ display:flex; flex-direction:column; gap:10px; margin:0 0 16px; }
    .bn-item{ display:flex; align-items:center; gap:14px; background:#fff; border:1px solid #e6eaef; border-radius:12px; padding:12px 14px; }
    .bn-item__grip{ cursor:grab; color:#9aa4b2; font-size:20px; line-height:1; user-select:none; padding:0 4px; }
    .bn-item__preview{ flex:1; min-width:0; }
    .bn-item__actions{ display:flex; gap:8px; }

    /* Mini-podgląd węzła (odwzorowanie strony) — grafika w kole (z kadrowaniem) */
    .bn-prev{ display:grid; gap:16px; align-items:center; }
    .bn-prev--left{ grid-template-columns:auto 1fr; }
    .bn-prev--right{ grid-template-columns:1fr auto; }
    .bn-prev--right .bn-prev__media{ order:2; }
    .bn-prev--noimg{ grid-template-columns:1fr; }
    .bn-prev__media{ width:84px; height:84px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
    .bn-prev__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; transform-origin:center; }
    .bn-prev__heading{ font-size:16px; font-weight:700; margin:0 0 4px; }
    .bn-prev__text{ font-size:13px; color:#5a6674; line-height:1.45; max-height:4.4em; overflow:hidden; }
    .bn-prev__text p{ margin:0 0 4px; }
    .bn-prev__text img{ max-width:100%; height:auto; }

    .bn-add{ width:100%; display:flex; align-items:center; justify-content:center; gap:10px; padding:22px; border:2px dashed #c7d0da; border-radius:14px; background:#fbfdff; color:#2563eb; font-weight:700; font-size:16px; cursor:pointer; }
    .bn-add:hover{ background:#f2f7ff; border-color:#2563eb; }
    .bn-add__plus{ font-size:26px; line-height:1; }
    .sortable-ghost{ opacity:.4; }

    /* Modal-kreator */
    .bmodal{ position:fixed; inset:0; z-index:2000; display:flex; align-items:flex-start; justify-content:center; padding:28px 16px; background:rgba(15,23,42,.55); overflow:auto; }
    .bmodal[hidden]{ display:none; }
    .bmodal__dialog{ background:#fff; border-radius:16px; width:min(1000px,100%); box-shadow:0 24px 64px rgba(0,0,0,.3); }
    .bmodal__head{ padding:16px 22px; border-bottom:1px solid #eef1f4; font-weight:800; font-size:18px; }
    .bmodal__grid{ display:grid; grid-template-columns:1fr 1fr; gap:0; }
    .bmodal__preview{ padding:22px; border-right:1px solid #eef1f4; background:#fafbfc; }
    .bmodal__form{ padding:22px; }
    .bmodal__foot{ display:flex; justify-content:flex-end; gap:10px; padding:16px 22px; border-top:1px solid #eef1f4; }
    @media (max-width:820px){ .bmodal__grid{ grid-template-columns:1fr; } .bmodal__preview{ border-right:0; border-bottom:1px solid #eef1f4; } }

    /* Podgląd węzła w modalu — grafika w kole (z kadrowaniem) */
    .pv-label{ font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#9aa4b2; margin-bottom:10px; }
    .pv-node{ display:grid; gap:18px; align-items:center; }
    .pv-node--left{ grid-template-columns:auto 1fr; }
    .pv-node--right{ grid-template-columns:1fr auto; }
    .pv-node--right .pv-node__media{ order:2; }
    .pv-node--noimg{ grid-template-columns:1fr; }
    .pv-node__media{ width:150px; height:150px; border-radius:50%; overflow:hidden; background:#eef2f7; position:relative; }
    .pv-node__media img{ position:absolute; top:50%; left:50%; width:100%; height:100%; object-fit:contain; transform-origin:center; }
    .pv-node__heading{ font-size:20px; margin:0 0 8px; }
    .pv-node__text{ font-size:14px; line-height:1.55; color:#3f4a58; }
    .pv-node__text img{ max-width:100%; border-radius:8px; }

    #bn-editor{ background:#fff; min-height:160px; }
    .side-opts{ display:flex; gap:16px; flex-wrap:wrap; }
    .thumb-row{ display:flex; align-items:center; gap:12px; margin-bottom:8px; }
    .thumb-row img{ width:56px; height:56px; object-fit:contain; border-radius:50%; background:#eef2f7; }
    .crop-row{ display:flex; align-items:center; gap:10px; margin-bottom:6px; }
    .crop-row > span{ width:82px; color:#4a5568; font-size:13px; }
    .crop-row input[type=range]{ flex:1; }
    .crop-row output{ width:54px; text-align:right; font-variant-numeric:tabular-nums; font-size:13px; color:#334155; }
</style>
@endpush

@section('content')
<div class="panel-title">
    <h1>Wspieramy — edytor podstrony</h1>
    <a href="{{ route('beneficiaries') }}" target="_blank" class="btn btn-secondary">Podgląd strony ↗</a>
</div>

<div class="bn-list" id="bnList">
    @foreach($nodes as $node)
        <div class="bn-item" data-id="{{ $node->id }}">
            <span class="bn-item__grip" title="Przeciągnij, aby zmienić kolejność">⠿</span>
            <div class="bn-item__preview">
                <div class="bn-prev bn-prev--{{ $node->image ? ($node->imageRight() ? 'right' : 'left') : 'noimg' }}">
                    @if($node->image)
                        <div class="bn-prev__media">
                            <img src="{{ asset('storage/' . $node->image) }}" alt=""
                                 style="transform: translate(-50%,-50%) translate({{ $node->image_x }}%, {{ $node->image_y }}%) scale({{ $node->image_scale / 100 }});">
                        </div>
                    @endif
                    <div class="bn-prev__body" style="text-align: {{ $node->text_align }}">
                        <div class="bn-prev__heading">{{ $node->heading }}</div>
                        <div class="bn-prev__text">{!! $node->body_html !!}</div>
                    </div>
                </div>
            </div>
            <div class="bn-item__actions">
                <button type="button" class="btn btn-secondary btn-edit" data-id="{{ $node->id }}">Edytuj</button>
                <form method="POST" action="{{ route('panel.beneficiaries.destroy', $node) }}"
                      onsubmit="return confirm('Usunąć węzeł „{{ $node->heading }}”? Tej operacji nie można cofnąć.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

<button type="button" class="bn-add" id="bnAdd">
    <span class="bn-add__plus">+</span> Dodaj
</button>

@php
    $nodesData = $nodes->map(fn ($n) => [
        'id' => $n->id,
        'heading' => $n->heading,
        'image_side' => $n->image_side,
        'text_align' => $n->text_align,
        'image' => $n->image ? asset('storage/' . $n->image) : null,
        'image_scale' => $n->image_scale,
        'image_x' => $n->image_x,
        'image_y' => $n->image_y,
        'body_html' => $n->body_html ?? '',
        'update_url' => route('panel.beneficiaries.update', $n),
    ])->keyBy('id');
@endphp
<script id="bn-data" type="application/json">{!! $nodesData->toJson() !!}</script>

{{-- Modal-kreator --}}
<div class="bmodal" id="bnModal" hidden>
    <div class="bmodal__dialog">
        <div class="bmodal__head" id="bnModalTitle">Nowa pozycja</div>
        <form method="POST" id="bnForm" enctype="multipart/form-data" action="{{ route('panel.beneficiaries.store') }}">
            @csrf
            <div class="bmodal__grid">
                {{-- Podgląd na żywo --}}
                <div class="bmodal__preview">
                    <div class="pv-label">Podgląd</div>
                    <div class="pv-node pv-node--noimg" id="pvNode">
                        <div class="pv-node__media" id="pvMedia" style="display:none"><img id="pvImg" src="" alt=""></div>
                        <div class="pv-node__body" id="pvBody">
                            <h3 class="pv-node__heading" id="pvHeading">Nagłówek</h3>
                            <div class="pv-node__text" id="pvText"></div>
                        </div>
                    </div>
                </div>

                {{-- Formularz --}}
                <div class="bmodal__form">
                    <div class="form-group">
                        <label for="bn-heading">Nagłówek *</label>
                        <input type="text" id="bn-heading" name="heading" required maxlength="255" placeholder="np. Szkoły">
                    </div>

                    <div class="form-group">
                        <label>Grafika</label>
                        <div class="thumb-row" id="bn-thumb-row" hidden>
                            <img id="bn-thumb" src="" alt="">
                            <label style="font-weight:400"><input type="checkbox" name="remove_image" value="1" id="bn-remove" style="width:auto"> usuń grafikę</label>
                        </div>
                        <input type="file" id="bn-image" name="image_file" accept="image/*">
                    </div>

                    <div class="form-group" id="bn-crop">
                        <label>Dopasowanie grafiki w kole</label>
                        <div class="crop-row"><span>Skala</span><input type="range" name="image_scale" id="bn-scale" min="20" max="400" step="1" value="100"><output id="bn-scale-o">100%</output></div>
                        <div class="crop-row"><span>Poziomo</span><input type="range" name="image_x" id="bn-x" min="-100" max="100" step="1" value="0"><output id="bn-x-o">0</output></div>
                        <div class="crop-row"><span>Pionowo</span><input type="range" name="image_y" id="bn-y" min="-100" max="100" step="1" value="0"><output id="bn-y-o">0</output></div>
                    </div>

                    <div class="form-group">
                        <label>Położenie grafiki (desktop)</label>
                        <div class="side-opts">
                            <label style="font-weight:400"><input type="radio" name="image_side" value="left" checked> po lewej</label>
                            <label style="font-weight:400"><input type="radio" name="image_side" value="right"> po prawej</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Wyrównanie tekstu</label>
                        <div class="side-opts">
                            <label style="font-weight:400"><input type="radio" name="text_align" value="left" checked> do lewej</label>
                            <label style="font-weight:400"><input type="radio" name="text_align" value="center"> środek</label>
                            <label style="font-weight:400"><input type="radio" name="text_align" value="right"> do prawej</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tekst (pod nagłówkiem)</label>
                        <div id="bn-editor"></div>
                        <input type="hidden" name="body_html" id="bn-body">
                    </div>
                </div>
            </div>

            <div class="bmodal__foot">
                <button type="button" class="btn btn-secondary" id="bnCancel">Odrzuć</button>
                <button type="submit" class="btn btn-primary">Zatwierdź</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var nodes   = JSON.parse(document.getElementById('bn-data').textContent || '{}');
    var modal   = document.getElementById('bnModal');
    var form    = document.getElementById('bnForm');
    var title   = document.getElementById('bnModalTitle');
    var heading = document.getElementById('bn-heading');
    var imageIn = document.getElementById('bn-image');
    var thumbRow= document.getElementById('bn-thumb-row');
    var thumb   = document.getElementById('bn-thumb');
    var removeCb= document.getElementById('bn-remove');
    var bodyIn  = document.getElementById('bn-body');
    var scaleIn = document.getElementById('bn-scale');
    var xIn     = document.getElementById('bn-x');
    var yIn     = document.getElementById('bn-y');
    var STORE_URL = @json(route('panel.beneficiaries.store'));
    var UPLOAD_URL= @json(route('panel.products.editor-upload'));
    var CSRF = @json(csrf_token());

    // Podgląd
    var pvNode = document.getElementById('pvNode');
    var pvMedia= document.getElementById('pvMedia');
    var pvImg  = document.getElementById('pvImg');
    var pvBody = document.getElementById('pvBody');
    var pvHead = document.getElementById('pvHeading');
    var pvText = document.getElementById('pvText');
    var hasImage = false;

    var quill = new Quill('#bn-editor', {
        theme: 'snow',
        modules: { toolbar: {
            container: [['bold','italic','underline'], [{header:[2,3,false]}], [{list:'ordered'},{list:'bullet'}], ['link','image'], ['clean']],
            handlers: { image: function () {
                var input = document.createElement('input');
                input.type = 'file'; input.accept = 'image/*';
                input.onchange = function () {
                    var file = input.files[0]; if (!file) return;
                    var fd = new FormData(); fd.append('image', file); fd.append('_token', CSRF);
                    fetch(UPLOAD_URL, { method:'POST', body:fd, headers:{'X-CSRF-TOKEN':CSRF} })
                        .then(function (r) { return r.json(); })
                        .then(function (d) { if (d.url) { var rg = quill.getSelection(true); quill.insertEmbed(rg.index, 'image', d.url); } });
                };
                input.click();
            }}
        }}
    });

    function curVal(name){ var el = form.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : null; }

    function applyImgTransform(){
        pvImg.style.transform = 'translate(-50%,-50%) translate(' + xIn.value + '%, ' + yIn.value + '%) scale(' + (scaleIn.value / 100) + ')';
        document.getElementById('bn-scale-o').textContent = scaleIn.value + '%';
        document.getElementById('bn-x-o').textContent = xIn.value;
        document.getElementById('bn-y-o').textContent = yIn.value;
    }
    function setImageOptsDisabled(dis){
        [scaleIn, xIn, yIn].forEach(function (el) { el.disabled = dis; });
        form.querySelectorAll('input[name="image_side"]').forEach(function (r) { r.disabled = dis; });
        var crop = document.getElementById('bn-crop'); if (crop) crop.style.opacity = dis ? '.5' : '';
    }
    function updateLayout(){
        var side = curVal('image_side') || 'left';
        var variant = hasImage ? (side === 'right' ? 'right' : 'left') : 'noimg';
        pvNode.className = 'pv-node pv-node--' + variant;
        pvMedia.style.display = hasImage ? '' : 'none';
        pvBody.style.textAlign = curVal('text_align') || 'left';
    }
    function syncPreview(){
        pvHead.textContent = heading.value || 'Nagłówek';
        pvText.innerHTML = quill.root.innerHTML;
        updateLayout();
    }
    function setPreviewImage(url){
        if (url){ hasImage = true; pvImg.src = url; }
        else { hasImage = false; pvImg.removeAttribute('src'); }
        updateLayout();
        applyImgTransform();
    }

    heading.addEventListener('input', syncPreview);
    quill.on('text-change', syncPreview);
    form.querySelectorAll('input[name="image_side"], input[name="text_align"]').forEach(function (r) { r.addEventListener('change', syncPreview); });
    [scaleIn, xIn, yIn].forEach(function (s) { s.addEventListener('input', applyImgTransform); });
    imageIn.addEventListener('change', function () {
        var f = imageIn.files[0];
        if (f) { var rd = new FileReader(); rd.onload = function (e) { setPreviewImage(e.target.result); if (removeCb) removeCb.checked = false; setImageOptsDisabled(false); }; rd.readAsDataURL(f); }
    });
    if (removeCb) removeCb.addEventListener('change', function () {
        setImageOptsDisabled(removeCb.checked);
        if (removeCb.checked) setPreviewImage(null);
    });

    function setRadio(name, value){ var el = form.querySelector('input[name="' + name + '"][value="' + value + '"]'); if (el) el.checked = true; }

    function openModal(data) {
        form.reset();
        imageIn.value = '';
        scaleIn.value = data && data.image_scale ? data.image_scale : 100;
        xIn.value = data ? (data.image_x || 0) : 0;
        yIn.value = data ? (data.image_y || 0) : 0;
        if (data) {
            title.textContent = (data.heading || 'Pozycja') + ' - edycja';
            form.action = data.update_url;
            heading.value = data.heading;
            setRadio('image_side', data.image_side || 'left');
            setRadio('text_align', data.text_align || 'left');
            quill.root.innerHTML = data.body_html || '';
            if (data.image) { thumbRow.hidden = false; thumb.src = data.image; setPreviewImage(data.image); }
            else { thumbRow.hidden = true; setPreviewImage(null); }
        } else {
            title.textContent = 'Nowa pozycja';
            form.action = STORE_URL;
            heading.value = '';
            setRadio('image_side', 'left');
            setRadio('text_align', 'left');
            quill.root.innerHTML = '';
            thumbRow.hidden = true;
            setPreviewImage(null);
        }
        syncPreview();
        applyImgTransform();
        setImageOptsDisabled(false);
        modal.hidden = false;
    }
    function closeModal() { modal.hidden = true; }

    document.getElementById('bnAdd').addEventListener('click', function () { openModal(null); });
    document.querySelectorAll('.btn-edit').forEach(function (b) {
        b.addEventListener('click', function () { openModal(nodes[b.getAttribute('data-id')]); });
    });
    document.getElementById('bnCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    form.addEventListener('submit', function () { bodyIn.value = quill.root.innerHTML; });

    // Drag & drop — kolejność
    var list = document.getElementById('bnList');
    if (window.Sortable && list) {
        Sortable.create(list, {
            handle: '.bn-item__grip', animation: 150, ghostClass: 'sortable-ghost',
            onEnd: function () {
                var order = Array.prototype.map.call(list.querySelectorAll('.bn-item'), function (el) { return el.getAttribute('data-id'); });
                fetch(@json(route('panel.beneficiaries.reorder')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    }
})();
</script>
@endpush
