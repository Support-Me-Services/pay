<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#141d33">
    <title><?php echo $__env->yieldContent('title', config('shop.name')); ?></title>
    <meta name="description" content="<?php echo $__env->yieldContent('meta-description', 'Cyfrowa taca — wesprzyj swój kościół jednym dotknięciem telefonu. Szybko, bezpiecznie, bez gotówki.'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/church.css')); ?>?v=<?php echo e(filemtime(public_path('css/church.css'))); ?>">
    <style>
        .header-nav { display: flex; gap: 18px; }
        .header-nav a { color: #f6f1e6; opacity: .85; font-size: .92rem; font-weight: 500; text-decoration: none; }
        .header-nav a:hover { opacity: 1; color: #e2bf6a; }
    </style>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="<?php echo $__env->yieldContent('body-class'); ?>">
<?php if (! empty(trim($__env->yieldContent('bare')))): ?>
    <?php echo $__env->yieldContent('content'); ?>
<?php else: ?>
    <header class="site-header">
        <div class="bar">
            <a href="<?php echo e(route('home')); ?>" class="wordmark">
                <span class="plate" aria-hidden="true"></span><?php echo e(config('shop.name')); ?>

            </a>
            <nav class="header-nav">
                <a href="<?php echo e(route('careers')); ?>">Praca</a>
                <a href="<?php echo e(route('contact.show')); ?>">Kontakt</a>
            </nav>
        </div>
    </header>

    <?php echo $__env->yieldContent('content'); ?>

    <footer class="site-footer">
        <div class="container">
            <strong><?php echo e(config('shop.name')); ?></strong> — wpłaty obsługuje
            <span class="brand">nfcpay</span> (PayU).<br>
            Twoje wsparcie trafia w całości do wybranej parafii.
            <div class="fine">
                Operator płatności: MARCIN LULA · ul. dr Izabeli Wolfram 11, 05-800 Pruszków · NIP 8741624637<br>
                <a href="<?php echo e(route('careers')); ?>">Praca</a> · <a href="<?php echo e(route('contact.show')); ?>">Kontakt</a> · <a href="<?php echo e(route('regulamin')); ?>">Regulamin</a> · kontakt: kontakt@please-support-me.com · &copy; <?php echo e(date('Y')); ?>

            </div>
        </div>
    </footer>
<?php endif; ?>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/pay/unified/resources/views/storefront/church/layouts/public.blade.php ENDPATH**/ ?>