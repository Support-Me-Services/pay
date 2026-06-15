<?php $__env->startSection('title', ($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name')); ?>

<?php $__env->startSection('content'); ?>
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:620px">
            <h1 style="font-size:1.7rem"><?php echo e($position ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna'); ?></h1>
            <p class="text-muted mb-3">
                <?php if($position): ?>
                    Wyślij swoje zgłoszenie na to stanowisko. Załącz CV — odezwiemy się.
                <?php else: ?>
                    Nie znalazłeś oferty dla siebie? Zostaw nam swoje zgłoszenie, a odezwiemy się, gdy pojawi się odpowiednie stanowisko.
                <?php endif; ?>
            </p>

            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
            <?php endif; ?>

            <div class="card" style="padding:24px">
                <form method="POST"
                      action="<?php echo e($position ? route('careers.apply.store', $position) : route('careers.apply.general.store')); ?>"
                      enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
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

                    <div class="form-group">
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

                    <div class="form-group">
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

                    <div class="form-group">
                        <label for="message">List motywacyjny</label>
                        <textarea id="message" name="message" rows="6"><?php echo e(old('message')); ?></textarea>
                        <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group">
                        <label for="cv">CV <?php echo e($position ? '*' : ''); ?></label>
                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" <?php echo e($position ? 'required' : ''); ?>>
                        <small class="text-muted">Format PDF, DOC lub DOCX. Maksymalny rozmiar: 5 MB.</small>
                        <?php $__errorArgs = ['cv'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Wyślij zgłoszenie</button>
                    <a href="<?php echo e(route('careers')); ?>" class="btn btn-secondary">Wróć do ofert</a>
                </form>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/products/shop/aplikuj.blade.php ENDPATH**/ ?>