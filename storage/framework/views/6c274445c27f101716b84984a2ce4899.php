<?php $__env->startSection('title', 'Produkty'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Produkty (tagi NFC)</h1>
        <a href="<?php echo e(route('panel.products.create')); ?>" class="btn btn-primary btn-sm">+ Dodaj produkt</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Tag UID</th><th></th><th>Nazwa</th><th>Cena</th><th>Otwarcia</th><th>Zakupy</th><th>Przychód</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><code><?php echo e($row['product']->tag_uid); ?></code></td>
                            <td>
                                <?php if($row['product']->main_image): ?>
                                    <img src="<?php echo e(asset('storage/' . $row['product']->main_image)); ?>" alt=""
                                         style="width:44px;height:44px;object-fit:cover;border-radius:6px">
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?php echo e($row['product']->name); ?></td>
                            <td class="nowrap"><?php echo e($row['product']->pricePln()); ?> zł</td>
                            <td><?php echo e($row['stats']['opens']); ?></td>
                            <td><?php echo e($row['stats']['purchases']); ?></td>
                            <td class="fw-bold"><?php echo e(\App\Services\ShopStatsService::formatPln($row['stats']['revenue'])); ?></td>
                            <td>
                                <?php if($row['product']->active): ?>
                                    <span class="badge badge-success">aktywny</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">nieaktywny</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.products.edit', $row['product'])); ?>">Edytuj</a>
                                <a href="<?php echo e(route('panel.products.stats', $row['product'])); ?>">Statystyki</a>
                                <form method="POST" action="<?php echo e(route('panel.products.toggle', $row['product'])); ?>" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">
                                        <?php echo e($row['product']->active ? 'Dezaktywuj' : 'Aktywuj'); ?>

                                    </a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="9" class="text-muted">Brak produktów.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/products/index.blade.php ENDPATH**/ ?>