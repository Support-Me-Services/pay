<?php $__env->startSection('title', 'Praca — ' . config('shop.name')); ?>

<?php $__env->startSection('content'); ?>
    <section class="section" style="padding-top:32px">
        <div class="container" style="max-width:760px">
            <h1 style="font-size:1.7rem">Praca i wolontariat</h1>
            <p class="text-muted mb-3">Aktualne oferty. Kliknij „Aplikuj", a odezwiemy się.</p>

            <div style="display:grid;gap:16px">
                <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="card" style="padding:20px">
                        <h2 style="font-size:1.25rem;margin:0 0 8px"><?php echo e($position->title); ?></h2>
                        <?php if($position->location || $position->employment_type): ?>
                            <p class="text-muted" style="margin:0 0 10px">
                                <?php if($position->location): ?><?php echo e($position->location); ?><?php endif; ?>
                                <?php if($position->location && $position->employment_type): ?> · <?php endif; ?>
                                <?php if($position->employment_type): ?><?php echo e($position->employment_type); ?><?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if($position->description_html): ?>
                            <div style="margin-bottom:14px"><?php echo $position->description_html; ?></div>
                        <?php endif; ?>
                        <a href="<?php echo e(route('careers.apply', $position)); ?>" class="btn btn-primary">Aplikuj</a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-muted">Obecnie nie prowadzimy rekrutacji.</p>
                <?php endif; ?>

                <div style="margin-top:8px">
                    <p class="text-muted" style="margin:0 0 8px">Nie znalazłeś oferty dla siebie?</p>
                    <a href="<?php echo e(route('careers.apply.general')); ?>" class="btn btn-secondary">Aplikuj spontanicznie</a>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/products/shop/praca.blade.php ENDPATH**/ ?>