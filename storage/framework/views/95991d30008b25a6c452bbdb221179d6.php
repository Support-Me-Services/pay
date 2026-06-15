<?php $__env->startSection('title', 'Wiadomość'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Wiadomość</h1>
        <a href="<?php echo e(route('panel.messages.index')); ?>" class="btn btn-secondary btn-sm">← Wróć do listy</a>
    </div>

    <div class="card card-static mb-3" style="max-width:760px">
        <div class="card-body">
            <table class="table">
                <tbody>
                    <tr><th style="width:160px">Data</th><td><?php echo e($message->created_at?->format('Y-m-d H:i')); ?></td></tr>
                    <tr><th>Imię i nazwisko</th><td><?php echo e($message->name); ?></td></tr>
                    <tr><th>E-mail</th><td><a href="mailto:<?php echo e($message->email); ?>"><?php echo e($message->email); ?></a></td></tr>
                    <tr><th>Telefon</th><td><?php echo e($message->phone ?: '—'); ?></td></tr>
                    <tr><th>Temat</th><td><?php echo e($message->subject ?: '—'); ?></td></tr>
                </tbody>
            </table>

            <div class="form-group" style="margin-top:18px">
                <label>Wiadomość</label>
                <div style="white-space:pre-wrap;background:#fff;border:1px solid var(--line, #e2e8f0);border-radius:var(--radius, 10px);padding:14px"><?php echo e($message->message); ?></div>
            </div>

            <div class="d-flex gap-1">
                <a href="mailto:<?php echo e($message->email); ?>?subject=<?php echo e(rawurlencode('Re: ' . ($message->subject ?: 'Twoja wiadomość'))); ?>" class="btn btn-primary">Odpowiedz e-mailem</a>
                <form method="POST" action="<?php echo e(route('panel.messages.destroy', $message)); ?>"
                      onsubmit="return confirm('Usunąć wiadomość?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/messages/show.blade.php ENDPATH**/ ?>