<?php $__env->startSection('title', 'Wesprzyj: ' . $product->name . ' — ' . config('shop.name')); ?>
<?php $__env->startSection('meta-description', 'Złóż cyfrową tacę na rzecz parafii ' . $product->name . '. Szybka wpłata BLIK, bez gotówki.'); ?>

<?php
    // Sugerowane kwoty (zł). Domyślnie zaznaczona = cena bazowa parafii (zwykle 20 zł).
    $presets = [10, 20, 50, 100, 200, 500];
    $default = (int) round($product->price / 100);
    if (! in_array($default, $presets, true)) {
        $default = 20;
    }
?>

<?php $__env->startSection('content'); ?>
    <section class="don-hero">
        <div class="don-hero-art">
            <?php if($product->main_image): ?>
                <img src="<?php echo e(asset('storage/' . $product->main_image)); ?>" alt="<?php echo e($product->name); ?>">
            <?php endif; ?>
        </div>
        <div class="don-hero-inner">
            <a href="<?php echo e(route('home')); ?>" class="don-back">← wszystkie parafie</a>
            <?php if($product->city): ?>
                <div class="don-city"><?php echo e($product->city); ?></div>
            <?php endif; ?>
            <h1><?php echo e($product->name); ?></h1>
            <?php if($product->purpose): ?>
                <span class="don-purpose">✦ <?php echo e($product->purpose); ?></span>
            <?php endif; ?>
        </div>
    </section>

    <div class="don-body">
        <?php if(session('error')): ?>
            <div class="alert alert-error"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <?php if($product->description_html): ?>
            <div class="don-lead"><?php echo $product->description_html; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('product.buy', $product->slug)); ?>" id="giveForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="amount_pln" id="amountField" value="<?php echo e($default); ?>">

            <div class="give-card">
                <div class="give-label">Wybierz kwotę wsparcia</div>
                <div class="give-sub">Możesz wpłacić dowolną kwotę — sugerujemy <?php echo e($default); ?> zł.</div>

                <div class="amount-grid" role="group" aria-label="Kwota wsparcia">
                    <?php $__currentLoopData = $presets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" class="amount-opt <?php echo e($value === $default ? 'is-active' : ''); ?>"
                                data-amount="<?php echo e($value); ?>" aria-pressed="<?php echo e($value === $default ? 'true' : 'false'); ?>">
                            <?php echo e($value); ?><small>zł</small>
                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="custom-amount">
                    <label for="customAmount">Inna kwota</label>
                    <div class="amount-input">
                        <input type="number" id="customAmount" inputmode="numeric" min="2" max="5000" step="1"
                               placeholder="np. 30" aria-label="Inna kwota w złotych">
                        <span class="suffix">zł</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="give-sticky">
        <div class="inner">
            <button type="submit" form="giveForm" class="btn btn-gold btn-block" id="giveBtn">
                Wesprzyj — <span id="ctaAmount"><?php echo e($default); ?></span> zł
            </button>
            <div class="secure">🔒 Bezpieczna płatność BLIK · obsługuje PayU</div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const opts   = document.querySelectorAll('.amount-opt');
    const custom = document.getElementById('customAmount');
    const field  = document.getElementById('amountField');
    const cta     = document.getElementById('ctaAmount');
    const btn     = document.getElementById('giveBtn');
    const form    = document.getElementById('giveForm');

    function setAmount(value) {
        field.value = value;
        cta.textContent = value;
    }

    opts.forEach(function (opt) {
        opt.addEventListener('click', function () {
            opts.forEach(o => { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
            opt.classList.add('is-active');
            opt.setAttribute('aria-pressed', 'true');
            custom.value = '';
            setAmount(opt.dataset.amount);
        });
    });

    custom.addEventListener('input', function () {
        const v = parseInt(custom.value, 10);
        opts.forEach(o => { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
        if (!isNaN(v) && v > 0) {
            setAmount(v);
            // jeśli pokrywa się z presetem — podświetl go
            opts.forEach(o => { if (parseInt(o.dataset.amount, 10) === v) { o.classList.add('is-active'); o.setAttribute('aria-pressed', 'true'); } });
        } else {
            cta.textContent = '—';
        }
    });

    form.addEventListener('submit', function (e) {
        const v = parseInt(field.value, 10);
        if (isNaN(v) || v < 2) {
            e.preventDefault();
            custom.focus();
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Przenosimy do płatności…';
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/product.blade.php ENDPATH**/ ?>