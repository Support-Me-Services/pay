<?php $__env->startSection('title', 'Aplikacje'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Aplikacje
            <?php if($unread > 0): ?>
                <span class="badge badge-brand"><?php echo e($unread); ?> nowych</span>
            <?php endif; ?>
        </h1>
    </div>

    <?php if($filterPosition): ?>
        <div class="alert" style="background:#f7efdc;border:1px solid var(--line,#e2e8f0)">
            Filtr: oferta „<?php echo e($filterPosition->title); ?>".
            <a href="<?php echo e(route('panel.applications.index')); ?>">Pokaż wszystkie</a>
        </div>
    <?php endif; ?>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th><th>Kandydat</th><th>Oferta</th><th>CV</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr style="<?php echo e($application->is_read ? '' : 'font-weight:700'); ?>">
                            <td class="nowrap"><?php echo e($application->created_at?->format('Y-m-d H:i')); ?></td>
                            <td>
                                <?php echo e($application->name); ?><br>
                                <span class="text-muted" style="font-weight:400"><?php echo e($application->email); ?></span>
                            </td>
                            <td><?php echo e($application->position?->title ?: 'aplikacja spontaniczna'); ?></td>
                            <td>
                                <?php if($application->cv_path): ?>
                                    <a href="<?php echo e(route('panel.applications.cv', $application)); ?>">Pobierz</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($application->is_read): ?>
                                    <span class="badge badge-muted">przeczytane</span>
                                <?php else: ?>
                                    <span class="badge badge-success">nowe</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.applications.show', $application)); ?>">Otwórz</a>
                                <form method="POST" action="<?php echo e(route('panel.applications.destroy', $application)); ?>" style="display:inline"
                                      onsubmit="return confirm('Usunąć zgłoszenie?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-muted">Brak zgłoszeń.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/applications/index.blade.php ENDPATH**/ ?>