<?php $__env->startSection('title', 'Handlowcy'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Handlowcy</h1>
        <a href="<?php echo e(route('panel.salespeople.create')); ?>" class="btn btn-primary btn-sm">+ Dodaj handlowca</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Imię i nazwisko</th><th>Kontakt</th><th>Województwa</th><th>Parafie</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $salespeople; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="fw-bold"><?php echo e($sp->name); ?></td>
                            <td>
                                <?php echo e($sp->email ?: '—'); ?>

                                <?php if($sp->phone): ?><br><span class="text-muted" style="font-weight:400"><?php echo e($sp->phone); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <?php if($sp->voivodeships): ?>
                                    <?php echo e(implode(', ', $sp->voivodeships)); ?>

                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($sp->parishes_count > 0): ?>
                                    <a href="<?php echo e(route('panel.products.index', ['q' => $sp->name])); ?>"><?php echo e($sp->parishes_count); ?></a>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($sp->active): ?>
                                    <span class="badge badge-success">aktywny</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">nieaktywny</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.salespeople.edit', $sp)); ?>">Edytuj</a>
                                <form method="POST" action="<?php echo e(route('panel.salespeople.destroy', $sp)); ?>" style="display:inline"
                                      onsubmit="return confirm('Usunąć handlowca? Parafie stracą przypisanie.');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-muted">Brak handlowców.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/salespeople/index.blade.php ENDPATH**/ ?>