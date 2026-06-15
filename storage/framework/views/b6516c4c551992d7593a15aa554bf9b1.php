<?php $__env->startSection('title', 'Praca — ' . config('shop.name')); ?>
<?php $__env->startSection('meta-description', 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .careers-hero { text-align: center; padding: 56px 20px 28px; }
    .careers-hero .eyebrow { color: var(--gold-deep); font-weight: 600; letter-spacing: .14em; text-transform: uppercase; font-size: .78rem; }
    .careers-hero h1 { font-family: var(--display); font-weight: 600; font-size: clamp(2rem, 6vw, 3rem); color: var(--navy); margin: 10px 0 12px; }
    .careers-hero .lede { color: var(--ink-soft); max-width: 620px; margin: 0 auto; font-size: 1.05rem; }

    .job-list { max-width: 760px; margin: 0 auto; padding: 0 18px 64px; display: grid; gap: 18px; }
    .job-card { background: var(--paper-card); border: 1px solid var(--line); border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm); padding: 24px 24px 22px; }
    .job-card h2 { font-family: var(--display); font-weight: 600; font-size: 1.4rem; color: var(--navy); margin: 0 0 10px; }
    .job-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .job-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 600;
        color: var(--gold-deep); background: #f7efdc; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; }
    .job-desc { color: var(--ink-soft); font-size: .98rem; line-height: 1.6; margin-bottom: 18px; }
    .job-desc :is(p, ul, ol) { margin: 0 0 .6em; }
    .job-desc ul, .job-desc ol { padding-left: 1.2em; }
    .job-empty { text-align: center; color: var(--muted); padding: 40px 0; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="careers-hero">
        <div class="eyebrow">Dołącz do nas</div>
        <h1>Praca i wolontariat</h1>
        <p class="lede">Szukamy osób, które chcą tworzyć coś dobrego razem z nami. Poniżej znajdziesz aktualne oferty — kliknij „Aplikuj", a odezwiemy się.</p>
    </section>

    <div class="job-list">
        <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <article class="job-card">
                <h2><?php echo e($position->title); ?></h2>
                <?php if($position->location || $position->employment_type): ?>
                    <div class="job-chips">
                        <?php if($position->location): ?>
                            <span class="job-chip">📍 <?php echo e($position->location); ?></span>
                        <?php endif; ?>
                        <?php if($position->employment_type): ?>
                            <span class="job-chip">🕒 <?php echo e($position->employment_type); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if($position->description_html): ?>
                    <div class="job-desc"><?php echo $position->description_html; ?></div>
                <?php endif; ?>
                <a href="<?php echo e(route('careers.apply', $position)); ?>" class="btn btn-gold">Aplikuj</a>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="job-empty">Obecnie nie prowadzimy rekrutacji. Zapraszamy ponownie wkrótce.</p>
        <?php endif; ?>

        <div style="text-align:center;padding:12px 0 8px">
            <p style="color:var(--ink-soft);margin:0 0 12px">Nie znalazłeś oferty dla siebie?</p>
            <a href="<?php echo e(route('careers.apply.general')); ?>" class="btn btn-navy">Aplikuj spontanicznie</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/church/shop/praca.blade.php ENDPATH**/ ?>