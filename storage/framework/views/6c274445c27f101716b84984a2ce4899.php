<?php $__env->startSection('title', 'Parafie'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Parafie (tagi NFC)</h1>
        <a href="<?php echo e(route('panel.products.create')); ?>" class="btn btn-primary btn-sm">+ Dodaj parafię</a>
    </div>

    
    <div class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <a href="<?php echo e(route('panel.products.index', array_filter(['q' => $q]))); ?>"
           class="btn btn-sm <?php echo e($status ? 'btn-secondary' : 'btn-primary'); ?>">Wszystkie (<?php echo e($total); ?>)</a>
        <?php $__currentLoopData = \App\Modules\Storefront\Models\Product::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('panel.products.index', array_filter(['status' => $key, 'q' => $q]))); ?>"
               class="btn btn-sm <?php echo e($status === $key ? 'btn-primary' : 'btn-secondary'); ?>">
                <?php echo e($label); ?> (<?php echo e($statusCounts[$key] ?? 0); ?>)
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <form method="GET" action="<?php echo e(route('panel.products.index')); ?>" class="d-flex gap-1 mb-3" style="flex-wrap:wrap">
        <?php if($status): ?><input type="hidden" name="status" value="<?php echo e($status); ?>"><?php endif; ?>
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Szukaj: nazwa, miasto, województwo…"
               style="flex:1;min-width:240px;max-width:420px">
        <button type="submit" class="btn btn-primary btn-sm">Szukaj</button>
        <?php if($q !== ''): ?>
            <a href="<?php echo e(route('panel.products.index', array_filter(['status' => $status]))); ?>" class="btn btn-secondary btn-sm">Wyczyść</a>
        <?php endif; ?>
    </form>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nazwa</th><th>Miasto / województwo</th><th>Status</th><th>Handlowiec</th>
                        <th>Tag NFC</th><th>Wpłaty</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $parishes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parish): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="fw-bold"><?php echo e($parish->name); ?></td>
                            <td>
                                <?php echo e($parish->city ?: '—'); ?>

                                <?php if($parish->voivodeship): ?>
                                    <br><span class="text-muted" style="font-weight:400"><?php echo e($parish->voivodeship); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php [$bg, $fg] = $parish->statusColors(); ?>
                                <span class="badge" style="background:<?php echo e($bg); ?>;color:<?php echo e($fg); ?>;font-weight:600"><?php echo e($parish->statusLabel()); ?></span>
                            </td>
                            <td><?php echo e($parish->salesperson?->name ?: '—'); ?></td>
                            <td><code><?php echo e($parish->tag_uid); ?></code></td>
                            <td><?php echo e($parish->orders_count); ?></td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.products.edit', $parish)); ?>">Edytuj</a>
                                <a href="<?php echo e(route('panel.products.stats', $parish)); ?>">Statystyki</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-muted">Brak parafii spełniających kryteria.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/products/index.blade.php ENDPATH**/ ?>