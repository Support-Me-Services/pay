<?php $__env->startSection('title', 'Zgłoszenie'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Zgłoszenie rekrutacyjne</h1>
        <a href="<?php echo e(route('panel.applications.index')); ?>" class="btn btn-secondary btn-sm">← Wróć do listy</a>
    </div>

    <div class="card card-static mb-3" style="max-width:760px">
        <div class="card-body">
            <table class="table">
                <tbody>
                    <tr><th style="width:160px">Data</th><td><?php echo e($application->created_at?->format('Y-m-d H:i')); ?></td></tr>
                    <tr><th>Imię i nazwisko</th><td><?php echo e($application->name); ?></td></tr>
                    <tr><th>E-mail</th><td><a href="mailto:<?php echo e($application->email); ?>"><?php echo e($application->email); ?></a></td></tr>
                    <tr><th>Telefon</th><td><?php echo e($application->phone ?: '—'); ?></td></tr>
                    <tr><th>Oferta</th><td><?php echo e($application->position?->title ?: 'aplikacja spontaniczna'); ?></td></tr>
                    <tr>
                        <th>CV</th>
                        <td>
                            <?php if($application->cv_path): ?>
                                <a href="<?php echo e(route('panel.applications.cv', $application)); ?>">
                                    <?php echo e($application->cv_original_name ?: 'Pobierz CV'); ?>

                                </a>
                            <?php else: ?>
                                <span class="text-muted">brak załączonego CV</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="form-group" style="margin-top:18px">
                <label>List motywacyjny</label>
                <div style="white-space:pre-wrap;background:#fff;border:1px solid var(--line, #e2e8f0);border-radius:var(--radius, 10px);padding:14px"><?php echo e($application->message ?: '—'); ?></div>
            </div>

            <div class="d-flex gap-1">
                <a href="mailto:<?php echo e($application->email); ?>?subject=<?php echo e(rawurlencode('Re: aplikacja' . ($application->position ? ' — ' . $application->position->title : ''))); ?>" class="btn btn-primary">Odpowiedz e-mailem</a>
                <?php if($application->cv_path): ?>
                    <a href="<?php echo e(route('panel.applications.cv', $application)); ?>" class="btn btn-secondary">Pobierz CV</a>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('panel.applications.destroy', $application)); ?>"
                      onsubmit="return confirm('Usunąć zgłoszenie?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/applications/show.blade.php ENDPATH**/ ?>