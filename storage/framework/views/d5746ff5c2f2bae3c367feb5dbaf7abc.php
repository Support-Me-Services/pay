<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Panel sklepu'); ?> — <?php echo e(config('shop.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/theme.css')); ?>?v=<?php echo e(filemtime(public_path('css/theme.css'))); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>
<div class="panel-wrap">
    <aside class="panel-sidebar">
        <div class="brand"><?php echo e(config('shop.name')); ?><span class="dot">.</span></div>
        <?php
            $unreadMessages = \App\Modules\Storefront\Models\ContactMessage::where('is_read', false)->count();
            $unreadApplications = \App\Modules\Storefront\Models\JobApplication::where('is_read', false)->count();
        ?>
        <nav class="panel-nav">
            <a href="<?php echo e(route('panel.dashboard')); ?>" class="<?php echo e(request()->routeIs('panel.dashboard') ? 'active' : ''); ?>">Dashboard</a>
            <a href="<?php echo e(route('panel.products.index')); ?>" class="<?php echo e(request()->routeIs('panel.products.*') || request()->routeIs('panel.parishes.*') ? 'active' : ''); ?>">Parafie</a>
            <a href="<?php echo e(route('panel.salespeople.index')); ?>" class="<?php echo e(request()->routeIs('panel.salespeople.*') ? 'active' : ''); ?>">Handlowcy</a>
            <a href="<?php echo e(route('panel.positions.index')); ?>" class="<?php echo e(request()->routeIs('panel.positions.*') ? 'active' : ''); ?>">Praca</a>
            <a href="<?php echo e(route('panel.applications.index')); ?>" class="<?php echo e(request()->routeIs('panel.applications.*') ? 'active' : ''); ?>">
                Aplikacje
                <?php if($unreadApplications > 0): ?>
                    <span class="badge badge-brand" style="margin-left:6px"><?php echo e($unreadApplications); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo e(route('panel.messages.index')); ?>" class="<?php echo e(request()->routeIs('panel.messages.*') ? 'active' : ''); ?>">
                Wiadomości
                <?php if($unreadMessages > 0): ?>
                    <span class="badge badge-brand" style="margin-left:6px"><?php echo e($unreadMessages); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo e(route('home')); ?>" target="_blank">Podgląd sklepu ↗</a>
            <div class="nav-sep"></div>
            <form method="POST" action="<?php echo e(route('panel.logout')); ?>">
                <?php echo csrf_field(); ?>
                <a href="#" onclick="this.closest('form').submit(); return false;">Wyloguj</a>
            </form>
        </nav>
    </aside>
    <main class="panel-main">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-error"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/pay/unified/resources/views/storefront/common/layouts/panel.blade.php ENDPATH**/ ?>