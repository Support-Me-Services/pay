<?php $__env->startSection('title', 'Logowanie — panel bramki'); ?>
<?php $__env->startSection('bare', true); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h2 class="text-center">nfc<span class="text-brand">pay</span></h2>
            <p class="text-muted text-center mb-3">Panel bramki płatności</p>

            <form method="POST" action="<?php echo e(route('panel.login.post')); ?>">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus>
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
                    <label for="password">Hasło</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Zaloguj się</button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/gateway/panel/login.blade.php ENDPATH**/ ?>