<?php $__env->startSection('title', 'Praca'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Praca — stanowiska</h1>
        <a href="<?php echo e(route('panel.positions.create')); ?>" class="btn btn-primary btn-sm">+ Dodaj stanowisko</a>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Kol.</th><th>Tytuł</th><th>Lokalizacja</th><th>Rodzaj</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($position->sort); ?></td>
                            <td class="fw-bold"><?php echo e($position->title); ?></td>
                            <td><?php echo e($position->location ?: '—'); ?></td>
                            <td><?php echo e($position->employment_type ?: '—'); ?></td>
                            <td>
                                <?php if($position->active): ?>
                                    <span class="badge badge-success">aktywne</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">nieaktywne</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.positions.edit', $position)); ?>">Edytuj</a>
                                <form method="POST" action="<?php echo e(route('panel.positions.toggle', $position)); ?>" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">
                                        <?php echo e($position->active ? 'Dezaktywuj' : 'Aktywuj'); ?>

                                    </a>
                                </form>
                                <form method="POST" action="<?php echo e(route('panel.positions.destroy', $position)); ?>" style="display:inline"
                                      onsubmit="return confirm('Usunąć stanowisko?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-muted">Brak stanowisk.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/positions/index.blade.php ENDPATH**/ ?>