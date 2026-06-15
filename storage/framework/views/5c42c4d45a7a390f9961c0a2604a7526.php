<?php $__env->startSection('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name')); ?>
<?php $__env->startSection('meta-description', 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .apply-hero { text-align: center; padding: 56px 20px 24px; }
    .apply-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .apply-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(1.8rem, 5vw, 2.6rem); color: var(--navy); margin: 10px 0 12px; }
    .apply-hero .lede { color: var(--ink-soft); max-width: 560px; margin: 0 auto; font-size: 1.05rem; }

    .apply-wrap { max-width: 620px; margin: 0 auto; padding: 0 18px 64px; }
    .apply-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow); padding: 28px 26px; }

    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: .88rem; font-weight: 600; color: var(--ink-soft); margin-bottom: 7px; }
    .field input, .field textarea { width: 100%; box-sizing: border-box; font-family: var(--ui); font-size: 1rem;
        color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 12px 14px; }
    .field input[type=file] { padding: 10px 12px; background: #fbf7ee; }
    .field input:focus, .field textarea:focus { outline: none; border-color: var(--gold); box-shadow: var(--shadow-gold); }
    .field textarea { resize: vertical; min-height: 130px; }
    .field .hint { font-size: .78rem; color: var(--muted); margin-top: 6px; }
    .field-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .field-row .field { flex: 1; min-width: 200px; }
    .form-error { color: var(--error); font-size: .82rem; margin-top: 6px; }

    .alert-success { background: #e8f4ed; border: 1px solid #aed5c1; color: #1f5f44; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="apply-hero">
        <div class="eyebrow">Aplikuj</div>
        <h1><?php echo e($position ? $position->title : 'Aplikacja spontaniczna'); ?></h1>
        <p class="lede">
            <?php if($position): ?>
                Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.
            <?php else: ?>
                Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a skontaktujemy się, gdy pojawi się odpowiednie stanowisko.
            <?php endif; ?>
        </p>
    </section>

    <div class="apply-wrap">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
        <?php endif; ?>

        <div class="apply-card">
            <form method="POST"
                  action="<?php echo e($position ? route('careers.apply.store', $position) : route('careers.apply.general.store')); ?>"
                  enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="field-row">
                    <div class="field">
                        <label for="name">Imię i nazwisko *</label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name')); ?>" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="field">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="field">
                    <label for="phone">Telefon</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e(old('phone')); ?>">
                    <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="field">
                    <label for="message">List motywacyjny</label>
                    <textarea id="message" name="message"><?php echo e(old('message')); ?></textarea>
                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="field">
                    <label for="cv">CV <?php echo e($position ? '*' : ''); ?></label>
                    <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" <?php echo e($position ? 'required' : ''); ?>>
                    <div class="hint">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</div>
                    <?php $__errorArgs = ['cv'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="btn btn-gold btn-block">Wyślij zgłoszenie</button>
            </form>
        </div>

        <p style="text-align:center;margin-top:18px">
            <a href="<?php echo e(route('careers')); ?>" style="color:var(--gold-deep)">← Wróć do ofert</a>
        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/aplikuj.blade.php ENDPATH**/ ?>