<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', config('shop.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/theme.css')); ?>?v=<?php echo e(filemtime(public_path('css/theme.css'))); ?>">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="<?php echo $__env->yieldContent('body-class'); ?>">
<?php if (! empty(trim($__env->yieldContent('bare')))): ?>
    <?php echo $__env->yieldContent('content'); ?>
<?php else: ?>
    <header class="site-header">
        <div class="container">
            <a href="<?php echo e(route('home')); ?>" class="site-logo"><?php echo e(config('shop.name')); ?><span class="dot">.</span></a>
            <span class="badge badge-brand">sprzedaż bezobsługowa</span>
        </div>
    </header>

    <?php echo $__env->yieldContent('content'); ?>

    <footer class="site-footer">
        <div class="container">
            <strong><?php echo e(config('shop.name')); ?></strong> — płatności obsługuje
            <strong style="color:var(--brand)">nfcpay</strong> (PayU)<br>
            <span class="small">MICHAŁ ŻOŁĄDKOWICZ SULI · ul. Jana Kilińskiego 13/36, 19-300 Ełk · NIP 8481749996 · REGON 280508388</span><br>
            <span class="small"><a href="<?php echo e(route('regulamin')); ?>">Regulamin sklepu</a> · kontakt: michal@suli.pl · tel. 691 102 010 · &copy; <?php echo e(date('Y')); ?></span>
        </div>
    </footer>
<?php endif; ?>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/pay/unified/resources/views/storefront/products/layouts/public.blade.php ENDPATH**/ ?>