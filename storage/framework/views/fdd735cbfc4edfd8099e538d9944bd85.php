<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1>Dashboard</h1>
        <span class="badge badge-brand">tryb płatności: <?php echo e(config('shop.payment_mode')); ?></span>
    </div>

    <?php
        $dashUnread = \App\Modules\Storefront\Models\ContactMessage::where('is_read', false)->count();
    ?>
    <?php if($dashUnread > 0): ?>
        <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <span>Masz <strong><?php echo e($dashUnread); ?></strong> nieprzeczytanych wiadomości z formularza kontaktowego.</span>
            <a href="<?php echo e(route('panel.messages.index')); ?>" class="btn btn-primary btn-sm">Zobacz wiadomości</a>
        </div>
    <?php endif; ?>

    <h3 class="mb-1">Łącznie</h3>
    <div class="stat-grid">
        <div class="stat-card stat-brand"><div class="stat-label">Przychód</div><div class="stat-value"><?php echo e(\App\Services\ShopStatsService::formatPln($total['revenue'])); ?></div></div>
        <div class="stat-card"><div class="stat-label">Zakupy</div><div class="stat-value"><?php echo e($total['purchases']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia tagów</div><div class="stat-value"><?php echo e($total['opens']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Konwersja</div><div class="stat-value"><?php echo e($total['conversion']); ?>%</div><div class="stat-sub">zakupy / otwarcia</div></div>
    </div>

    <h3 class="mb-1">Ostatnie 30 dni</h3>
    <div class="stat-grid">
        <div class="stat-card stat-brand"><div class="stat-label">Przychód 30 dni</div><div class="stat-value"><?php echo e(\App\Services\ShopStatsService::formatPln($last30['revenue'])); ?></div></div>
        <div class="stat-card"><div class="stat-label">Zakupy 30 dni</div><div class="stat-value"><?php echo e($last30['purchases']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Otwarcia 30 dni</div><div class="stat-value"><?php echo e($last30['opens']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Konwersja 30 dni</div><div class="stat-value"><?php echo e($last30['conversion']); ?>%</div></div>
    </div>

    <div class="chart-card">
        <h3>Zakupy — dzień po dniu (30 dni)</h3>
        <canvas id="dailyChart" height="90"></canvas>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($series['labels'], 15, 512) ?>,
        datasets: [{
            label: 'Zakupy',
            data: <?php echo json_encode($series['counts'], 15, 512) ?>,
            backgroundColor: '#E20074',
            borderRadius: 4,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/dashboard.blade.php ENDPATH**/ ?>