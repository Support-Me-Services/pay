<?php $__env->startSection('title', 'Praca — ' . config('shop.name')); ?>
<?php $__env->startSection('meta-description', 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .careers-hero { text-align: center; padding: 56px 20px 28px; }
    .careers-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .careers-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(2rem, 6vw, 3rem); color: var(--navy); margin: 10px 0 12px; }
    .careers-hero .lede { color: var(--ink-soft); max-width: 620px; margin: 0 auto; font-size: 1.05rem; }

    .job-list { max-width: 760px; margin: 0 auto; padding: 0 18px 64px; display: grid; gap: 18px; }
    .job-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm); padding: 24px 24px 22px; scroll-margin-top: 80px; }
    .job-card h2 { font-family: var(--display); font-weight: 600; font-size: 1.4rem; color: var(--navy); margin: 0 0 10px; }
    .job-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .job-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 600;
        color: var(--gold-deep); background: #f7efdc; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; }
    .job-desc { color: var(--ink-soft); font-size: .98rem; line-height: 1.6; margin-bottom: 18px; }
    .job-desc :is(p, ul, ol) { margin: 0 0 .6em; }
    .job-desc ul, .job-desc ol { padding-left: 1.2em; }
    .job-empty { text-align: center; color: var(--muted); padding: 40px 0; }

    /* Formularz aplikacji WBUDOWANY w ofertę */
    .apply-box { border-top: 1px dashed var(--line); padding-top: 16px; margin-top: 4px; }
    .apply-box > summary { list-style: none; cursor: pointer; display: inline-flex; }
    .apply-box > summary::-webkit-details-marker { display: none; }
    .apply-box > summary .btn { pointer-events: none; }
    .apply-box[open] > summary { margin-bottom: 16px; }
    .apply-inner { animation: applyfade .2s ease; }
    @keyframes applyfade { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: .86rem; font-weight: 600; color: var(--ink-soft); margin-bottom: 6px; }
    .field input, .field textarea { width: 100%; box-sizing: border-box; font-family: var(--ui); font-size: 1rem;
        color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 11px 13px; }
    .field input[type=file] { padding: 9px 11px; background: #fbf7ee; }
    .field input:focus, .field textarea:focus { outline: none; border-color: var(--gold); box-shadow: var(--shadow-gold); }
    .field textarea { resize: vertical; min-height: 110px; }
    .field .hint { font-size: .77rem; color: var(--muted); margin-top: 5px; }
    .field-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .field-row .field { flex: 1; min-width: 190px; }
    .form-error { color: var(--error); font-size: .82rem; margin-top: 5px; }
    .alert-success { background: #e8f4ed; border: 1px solid #aed5c1; color: #1f5f44; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="careers-hero">
        <div class="eyebrow">Dołącz do nas</div>
        <h1>Praca i wolontariat</h1>
        <p class="lede">Szukamy osób, które chcą tworzyć coś dobrego razem z nami. Rozwiń ofertę i wyślij zgłoszenie wraz z CV — odezwiemy się.</p>
    </section>

    <div class="job-list">
        <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $isOpen = old('apply_position') == $position->id || session('apply_done') === (string) $position->id; ?>
            <article class="job-card" id="oferta-<?php echo e($position->id); ?>">
                <h2><?php echo e($position->title); ?></h2>
                <?php if($position->location || $position->employment_type): ?>
                    <div class="job-chips">
                        <?php if($position->location): ?><span class="job-chip">📍 <?php echo e($position->location); ?></span><?php endif; ?>
                        <?php if($position->employment_type): ?><span class="job-chip">🕒 <?php echo e($position->employment_type); ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if($position->description_html): ?>
                    <div class="job-desc"><?php echo $position->description_html; ?></div>
                <?php endif; ?>

                <?php if(session('apply_done') === (string) $position->id && session('success')): ?>
                    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                <?php endif; ?>

                
                <details class="apply-box" <?php echo e($isOpen ? 'open' : ''); ?>>
                    <summary><span class="btn btn-gold">Aplikuj na to stanowisko</span></summary>
                    <div class="apply-inner">
                        <?php if(old('apply_position') == $position->id && $errors->any()): ?>
                            <div class="alert alert-error">Popraw zaznaczone pola i wyślij ponownie.</div>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo e(route('careers.apply.store', $position)); ?>" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="apply_position" value="<?php echo e($position->id); ?>">
                            <div class="field-row">
                                <div class="field">
                                    <label>Imię i nazwisko *</label>
                                    <input type="text" name="name" value="<?php echo e(old('apply_position') == $position->id ? old('name') : ''); ?>" required>
                                    <?php if(old('apply_position') == $position->id): ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                                </div>
                                <div class="field">
                                    <label>E-mail *</label>
                                    <input type="email" name="email" value="<?php echo e(old('apply_position') == $position->id ? old('email') : ''); ?>" required>
                                    <?php if(old('apply_position') == $position->id): ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                                </div>
                            </div>
                            <div class="field">
                                <label>Telefon</label>
                                <input type="text" name="phone" value="<?php echo e(old('apply_position') == $position->id ? old('phone') : ''); ?>">
                            </div>
                            <div class="field">
                                <label>List motywacyjny</label>
                                <textarea name="message"><?php echo e(old('apply_position') == $position->id ? old('message') : ''); ?></textarea>
                            </div>
                            <div class="field">
                                <label>CV *</label>
                                <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
                                <div class="hint">PDF, DOC lub DOCX, maks. 5 MB.</div>
                                <?php if(old('apply_position') == $position->id): ?><?php $__errorArgs = ['cv'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-gold btn-block">Wyślij zgłoszenie</button>
                        </form>
                    </div>
                </details>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="job-empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>
        <?php endif; ?>

        
        <?php $spontOpen = old('apply_position') === 'spontaniczna' || session('apply_done') === 'general'; ?>
        <article class="job-card" id="oferta-spontaniczna">
            <h2>Nie znalazłeś oferty dla siebie?</h2>
            <p class="job-desc">Zostaw nam swoje zgłoszenie — odezwiemy się, gdy pojawi się odpowiednie stanowisko.</p>
            <?php if(session('apply_done') === 'general' && session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <details class="apply-box" <?php echo e($spontOpen ? 'open' : ''); ?>>
                <summary><span class="btn btn-navy">Aplikuj spontanicznie</span></summary>
                <div class="apply-inner">
                    <?php if(old('apply_position') === 'spontaniczna' && $errors->any()): ?>
                        <div class="alert alert-error">Popraw zaznaczone pola i wyślij ponownie.</div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('careers.apply.general.store')); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="apply_position" value="spontaniczna">
                        <div class="field-row">
                            <div class="field">
                                <label>Imię i nazwisko *</label>
                                <input type="text" name="name" value="<?php echo e(old('apply_position') === 'spontaniczna' ? old('name') : ''); ?>" required>
                                <?php if(old('apply_position') === 'spontaniczna'): ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                            </div>
                            <div class="field">
                                <label>E-mail *</label>
                                <input type="email" name="email" value="<?php echo e(old('apply_position') === 'spontaniczna' ? old('email') : ''); ?>" required>
                                <?php if(old('apply_position') === 'spontaniczna'): ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                            </div>
                        </div>
                        <div class="field">
                            <label>Telefon</label>
                            <input type="text" name="phone" value="<?php echo e(old('apply_position') === 'spontaniczna' ? old('phone') : ''); ?>">
                        </div>
                        <div class="field">
                            <label>List motywacyjny</label>
                            <textarea name="message"><?php echo e(old('apply_position') === 'spontaniczna' ? old('message') : ''); ?></textarea>
                        </div>
                        <div class="field">
                            <label>CV</label>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx">
                            <div class="hint">PDF, DOC lub DOCX, maks. 5 MB (opcjonalnie).</div>
                            <?php if(old('apply_position') === 'spontaniczna'): ?><?php $__errorArgs = ['cv'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-navy btn-block">Wyślij zgłoszenie</button>
                    </form>
                </div>
            </details>
        </article>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/praca.blade.php ENDPATH**/ ?>