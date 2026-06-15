<?php $__env->startSection('title', config('shop.name') . ' — wesprzyj swój kościół'); ?>

<?php $__env->startSection('content'); ?>
    <section class="hero">
        <div class="eyebrow">Cyfrowa taca</div>
        <h1>Złóż <em>tacę</em><br>nie wyjmując portfela</h1>
        <p class="lede">Przyłóż telefon do tabliczki przy ołtarzu albo wybierz parafię z listy poniżej. Wpłata trafia w całości do kościoła.</p>
        <a href="#parafie" class="scroll-cue">
            wybierz parafię
            <span aria-hidden="true"></span>
        </a>
        
        <svg class="hero-arches" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,60 V30 C150,-10 250,-10 400,30 C550,-10 650,-10 800,30 C950,-10 1050,-10 1200,30 V60 Z"/>
        </svg>
    </section>

    <section class="section" id="parafie">
        <div class="container container-wide">
            <div class="section-head">
                <h2>Wybierz parafię</h2>
                <p>Każdy kościół ma własną tabliczkę NFC przy tacy. Tu znajdziesz te same parafie online.</p>
            </div>

            <div class="parish-list">
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('product.show', $product->slug)); ?>" class="parish">
                        <div class="parish-art">
                            <?php if($product->main_image): ?>
                                <img src="<?php echo e(asset('storage/' . $product->main_image)); ?>" alt="<?php echo e($product->name); ?>" loading="lazy">
                            <?php endif; ?>
                            <?php if($product->city): ?>
                                <span class="parish-city"><?php echo e($product->city); ?></span>
                            <?php endif; ?>
                            <span class="parish-name-over"><?php echo e($product->name); ?></span>
                        </div>
                        <div class="parish-body">
                            <?php if($product->purpose): ?>
                                <span class="parish-purpose">✦ <?php echo e($product->purpose); ?></span>
                            <?php endif; ?>
                            <div class="parish-cta">
                                <span class="from">już od <b><?php echo e($product->pricePln()); ?> zł</b></span>
                                <span class="go">Wesprzyj <span class="arrow">→</span></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="muted">Brak parafii.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="trust">
        <div class="row">
            <div class="item">
                <div class="k">BLIK</div>
                <div class="v">Płać aplikacją banku — bez przepisywania numerów kart.</div>
            </div>
            <div class="item">
                <div class="k">100%</div>
                <div class="v">Cała kwota trafia do wybranej parafii.</div>
            </div>
            <div class="item">
                <div class="k">3&nbsp;sek.</div>
                <div class="v">Tyle zajmuje złożenie cyfrowej tacy.</div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/index.blade.php ENDPATH**/ ?>