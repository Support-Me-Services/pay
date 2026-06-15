<?php $__env->startSection('title', $product->exists ? 'Edytuj parafię' : 'Dodaj parafię'); ?>

<?php $__env->startPush('head'); ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <style>
        #editor { background: #fff; min-height: 260px; }
        .ql-toolbar { border-radius: var(--radius) var(--radius) 0 0; background: #fff; }
        .ql-container { border-radius: 0 0 var(--radius) var(--radius); font-family: var(--font); font-size: 1rem; }
        .note-item { border:1px solid var(--line,#e2e8f0); border-radius:8px; padding:10px 12px; margin-bottom:8px; background:#fff; }
        .note-meta { font-size:.82rem; color:#64748b; margin-bottom:4px; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1><?php echo e($product->exists ? 'Edytuj: ' . $product->name : 'Dodaj parafię'); ?></h1>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    <?php endif; ?>

    
    <?php if($product->exists): ?>
        <?php [$bg, $fg] = $product->statusColors(); ?>
        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="d-flex gap-1" style="align-items:center;flex-wrap:wrap">
                    <span>Status:</span>
                    <span class="badge" style="background:<?php echo e($bg); ?>;color:<?php echo e($fg); ?>;font-weight:600"><?php echo e($product->statusLabel()); ?></span>
                    <span class="text-muted" style="margin:0 6px">→ przenieś do:</span>
                    <?php $__currentLoopData = \App\Modules\Storefront\Models\Product::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($key !== $product->status): ?>
                            <form method="POST" action="<?php echo e(route('panel.parishes.status', $product)); ?>" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="status" value="<?php echo e($key); ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"><?php echo e($label); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="form-hint" style="margin-top:6px">Status „Aktywna" publikuje parafię na stronie; pozostałe ją ukrywają (lead).</div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="productForm"
          action="<?php echo e($product->exists ? route('panel.products.update', $product) : route('panel.products.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if($product->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nazwa parafii *</label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name', $product->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="city">Miasto</label>
                        <input type="text" id="city" name="city" value="<?php echo e(old('city', $product->city)); ?>" placeholder="np. Kraków">
                        <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="voivodeship">Województwo</label>
                        <select id="voivodeship" name="voivodeship">
                            <option value="">— wybierz —</option>
                            <?php $__currentLoopData = \App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voiv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($voiv); ?>" <?php if(old('voivodeship', $product->voivodeship) === $voiv): echo 'selected'; endif; ?>><?php echo e($voiv); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['voivodeship'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="<?php echo e(old('phone', $product->phone)); ?>" placeholder="np. 600 100 200">
                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="website">Strona www</label>
                        <input type="text" id="website" name="website" value="<?php echo e(old('website', $product->website)); ?>" placeholder="np. parafia.pl">
                        <?php $__errorArgs = ['website'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php $__currentLoopData = \App\Modules\Storefront\Models\Product::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('status', $product->status ?? 'kontakt') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="salesperson_id">Handlowiec</label>
                        <select id="salesperson_id" name="salesperson_id">
                            <option value="">— brak —</option>
                            <?php $__currentLoopData = $salespeople; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($sp->id); ?>" <?php if((int) old('salesperson_id', $product->salesperson_id) === $sp->id): echo 'selected'; endif; ?>><?php echo e($sp->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['salesperson_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:160px">
                        <label for="price">Kwota wpłaty (zł) *</label>
                        <input type="text" id="price" name="price" inputmode="decimal"
                               value="<?php echo e(old('price', $product->exists ? number_format($product->price / 100, 2, ',', '') : '')); ?>" required>
                        <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group" style="flex:2;min-width:220px">
                        <label for="tag_uid">UID taga NFC *</label>
                        <input type="text" id="tag_uid" name="tag_uid" value="<?php echo e(old('tag_uid', $product->tag_uid)); ?>"
                               placeholder="np. TAG-S1-001" required>
                        <?php $__errorArgs = ['tag_uid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pickup_instruction">Instrukcja po wpłacie (pokazywana po opłaceniu)</label>
                    <textarea id="pickup_instruction" name="pickup_instruction" rows="3"
                              placeholder="np. Bóg zapłać za wsparcie parafii…"><?php echo e(old('pickup_instruction', $product->pickup_instruction)); ?></textarea>
                    <?php $__errorArgs = ['pickup_instruction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label>Opis parafii (WYSIWYG)</label>
                    <div id="editor"><?php echo old('description_html', $product->description_html); ?></div>
                    <textarea name="description_html" id="description_html" style="display:none"><?php echo e(old('description_html', $product->description_html)); ?></textarea>
                    <div class="form-hint">Wstaw zdjęcie ikoną obrazka w pasku narzędzi — plik wgra się na serwer.</div>
                    <?php $__errorArgs = ['description_html'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label for="main_image">Zdjęcie główne <?php echo e($product->main_image ? '(zostaw puste, by zachować obecne)' : ''); ?></label>
                    <?php if($product->main_image): ?>
                        <img src="<?php echo e(asset('storage/' . $product->main_image)); ?>" alt=""
                             style="width:96px;height:96px;object-fit:cover;border-radius:8px;margin-bottom:8px">
                    <?php endif; ?>
                    <input type="file" id="main_image" name="main_image" accept="image/*">
                    <?php $__errorArgs = ['main_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label for="gallery">Galeria (możesz wybrać wiele plików)</label>
                    <?php if($product->exists && $product->images->count()): ?>
                        <div class="d-flex gap-1 mb-1" style="flex-wrap:wrap">
                            <?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div style="position:relative">
                                    <img src="<?php echo e(asset('storage/' . $image->path)); ?>" alt=""
                                         style="width:72px;height:72px;object-fit:cover;border-radius:8px">
                                    <button type="button" class="btn btn-danger btn-sm"
                                            style="position:absolute;top:-6px;right:-6px;padding:0 7px;border-radius:50%"
                                            onclick="deleteImage(<?php echo e($image->id); ?>)">×</button>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                    <?php $__errorArgs = ['gallery.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="<?php echo e(route('panel.products.index')); ?>" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>

    
    <?php if($product->exists): ?>
        <div class="card card-static mb-3" style="max-width:840px" id="crmNotes"
             data-store-url="<?php echo e(route('panel.parishes.notes.store', $product)); ?>"
             data-destroy-url="<?php echo e(route('panel.parishes.notes.destroy', [$product, '__ID__'])); ?>">
            <div class="card-body">
                <h2 style="margin-top:0">Notatki CRM</h2>
                <div class="form-hint">Historia kontaktu — kiedy i co się wydarzyło.</div>

                <form id="noteForm" class="d-flex gap-2 mb-3" style="flex-wrap:wrap;align-items:flex-end">
                    <div class="form-group" style="flex:0 0 160px;margin-bottom:0">
                        <label for="note_type">Typ</label>
                        <select id="note_type" name="type">
                            <?php $__currentLoopData = \App\Modules\Storefront\Models\ParishNote::TYPES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    <?php $__empty_1 = true; $__currentLoopData = $product->notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="note-item" data-id="<?php echo e($note->id); ?>">
                            <div class="note-meta">
                                <strong><?php echo e($note->typeLabel()); ?></strong> · <?php echo e($note->created_at?->format('Y-m-d H:i')); ?>

                                <?php if($note->author): ?> · <?php echo e($note->author); ?> <?php endif; ?>
                                <a href="#" class="note-del" style="float:right;color:#b23b3b">Usuń</a>
                            </div>
                            <div><?php echo e($note->body); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-muted" id="noteEmpty">Brak notatek.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($product->exists): ?>
        <form method="POST" id="deleteImageForm" style="display:none">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
                            fetch(<?php echo json_encode(route('panel.products.editor-upload'), 15, 512) ?>, {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>},
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

    const deleteUrlTemplate = <?php echo json_encode($product->exists ? route('panel.products.images.delete', [$product, '__ID__']) : '') ?>;

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
        const csrf = <?php echo json_encode(csrf_token(), 15, 512) ?>;
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/products/form.blade.php ENDPATH**/ ?>