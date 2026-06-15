<?php $__env->startSection('title', 'Wiadomości'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Wiadomości
            <?php if($unread > 0): ?>
                <span class="badge badge-brand"><?php echo e($unread); ?> nowych</span>
            <?php endif; ?>
        </h1>
    </div>

    <div class="card card-static">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th><th>Nadawca</th><th>Temat</th><th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr style="<?php echo e($message->is_read ? '' : 'font-weight:700'); ?>">
                            <td class="nowrap"><?php echo e($message->created_at?->format('Y-m-d H:i')); ?></td>
                            <td>
                                <?php echo e($message->name); ?><br>
                                <span class="text-muted" style="font-weight:400"><?php echo e($message->email); ?></span>
                            </td>
                            <td><?php echo e($message->subject ?: '—'); ?></td>
                            <td>
                                <?php if($message->is_read): ?>
                                    <span class="badge badge-muted">przeczytane</span>
                                <?php else: ?>
                                    <span class="badge badge-success">nowe</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions nowrap">
                                <a href="<?php echo e(route('panel.messages.show', $message)); ?>">Otwórz</a>
                                <form method="POST" action="<?php echo e(route('panel.messages.destroy', $message)); ?>" style="display:inline"
                                      onsubmit="return confirm('Usunąć wiadomość?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <a href="#" onclick="this.closest('form').submit(); return false;">Usuń</a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-muted">Brak wiadomości.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/messages/index.blade.php ENDPATH**/ ?>