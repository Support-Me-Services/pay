<?php $__env->startSection('title', 'Kontakt — ' . config('shop.name')); ?>
<?php $__env->startSection('meta-description', 'Napisz do nas — odpowiemy najszybciej, jak to możliwe.'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .contact-hero { text-align: center; padding: 56px 20px 24px; }
    .contact-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .contact-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(2rem, 6vw, 3rem); color: var(--navy); margin: 10px 0 12px; }
    .contact-hero .lede { color: var(--ink-soft); max-width: 560px; margin: 0 auto; font-size: 1.05rem; }

    .contact-wrap { max-width: 620px; margin: 0 auto; padding: 0 18px 64px; }
    .contact-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow); padding: 28px 26px; }

    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: .88rem; font-weight: 600; color: var(--ink-soft); margin-bottom: 7px; }
    .field input, .field textarea { width: 100%; box-sizing: border-box; font-family: var(--ui); font-size: 1rem;
        color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 12px 14px; }
    .field input:focus, .field textarea:focus { outline: none; border-color: var(--gold); box-shadow: var(--shadow-gold); }
    .field textarea { resize: vertical; min-height: 140px; }
    .field-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .field-row .field { flex: 1; min-width: 200px; }
    .form-error { color: var(--error); font-size: .82rem; margin-top: 6px; }

    .alert-success { background: #e8f4ed; border: 1px solid #aed5c1; color: #1f5f44; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="contact-hero">
        <div class="eyebrow">Jesteśmy do dyspozycji</div>
        <h1>Kontakt</h1>
        <p class="lede">Masz pytanie lub chcesz aplikować na stanowisko? Napisz do nas — odpowiemy najszybciej, jak to możliwe.</p>
    </section>

    <div class="contact-wrap">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
        <?php endif; ?>

        <div class="contact-card">
            <form method="POST" action="<?php echo e(route('contact.store')); ?>">
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

                <div class="field-row">
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
                        <label for="subject">Temat</label>
                        <input type="text" id="subject" name="subject" value="<?php echo e(old('subject', $subject)); ?>">
                        <?php $__errorArgs = ['subject'];
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
                    <label for="message">Wiadomość *</label>
                    <textarea id="message" name="message" required><?php echo e(old('message')); ?></textarea>
                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="btn btn-gold btn-block">Wyślij wiadomość</button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/kontakt.blade.php ENDPATH**/ ?>