<?php $__env->startSection('title', config('shop.name') . ' — produkty'); ?>

<?php $__env->startSection('content'); ?>
    <section class="section" style="padding-top:32px">
        <div class="container">
            <h1 style="font-size:1.7rem">Nasze produkty</h1>
            <p class="text-muted mb-3">Zeskanuj tag NFC przy produkcie albo wybierz z listy.</p>

            <div class="product-grid">
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('product.show', $product->slug)); ?>" class="card product-card">
                        <div class="thumb">
                            <?php if($product->main_image): ?>
                                <img src="<?php echo e(asset('storage/' . $product->main_image)); ?>" alt="<?php echo e($product->name); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <span class="name"><?php echo e($product->name); ?></span>
                            <span class="price"><?php echo e($product->pricePln()); ?> zł</span>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-muted">Brak produktów.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/products/shop/index.blade.php ENDPATH**/ ?>