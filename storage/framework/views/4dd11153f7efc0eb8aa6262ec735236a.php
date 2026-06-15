<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie — panel · <?php echo e(config('shop.name')); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/theme.css')); ?>?v=<?php echo e(filemtime(public_path('css/theme.css'))); ?>">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <h2 class="text-center"><?php echo e(config('shop.name')); ?><span class="text-brand">.</span></h2>
            <p class="text-muted text-center mb-3">Panel sklepu</p>

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
</body>
</html>
<?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/login.blade.php ENDPATH**/ ?>