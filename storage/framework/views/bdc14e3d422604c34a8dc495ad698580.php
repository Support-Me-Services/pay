<?php $__env->startSection('title', $salesperson->exists ? 'Edytuj handlowca' : 'Dodaj handlowca'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel-title">
        <h1><?php echo e($salesperson->exists ? 'Edytuj: ' . $salesperson->name : 'Dodaj handlowca'); ?></h1>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-error">Popraw zaznaczone pola formularza.</div>
    <?php endif; ?>

    <?php
        $selectedVoiv = old('voivodeships', $salesperson->voivodeships ?? []);
        $selectedVoiv = is_array($selectedVoiv) ? $selectedVoiv : [];
    ?>

    <form method="POST"
          action="<?php echo e($salesperson->exists ? route('panel.salespeople.update', $salesperson) : route('panel.salespeople.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if($salesperson->exists): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Imię i nazwisko *</label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name', $salesperson->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="d-flex gap-2" style="flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo e(old('email', $salesperson->email)); ?>">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="<?php echo e(old('phone', $salesperson->phone)); ?>">
                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Obsługiwane województwa</label>
                    <div class="d-flex gap-1" style="flex-wrap:wrap">
                        <?php $__currentLoopData = \App\Modules\Storefront\Models\Salesperson::VOIVODESHIPS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voiv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label style="flex:0 0 220px;font-weight:400">
                                <input type="checkbox" name="voivodeships[]" value="<?php echo e($voiv); ?>" style="width:auto"
                                       <?php if(in_array($voiv, $selectedVoiv, true)): echo 'checked'; endif; ?>> <?php echo e($voiv); ?>

                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php $__errorArgs = ['voivodeships'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php $__errorArgs = ['voivodeships.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="active" value="1" style="width:auto"
                                  <?php if(old('active', $salesperson->exists ? $salesperson->active : true)): echo 'checked'; endif; ?>> aktywny</label>
                </div>

                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                    <a href="<?php echo e(route('panel.salespeople.index')); ?>" class="btn btn-secondary">Anuluj</a>
                </div>
            </div>
        </div>
    </form>

    
    <?php if($salesperson->exists && $salesperson->parishes->count()): ?>
        <div class="card card-static mb-3" style="max-width:840px">
            <div class="card-body">
                <h2 style="margin-top:0">Parafie tego handlowca (<?php echo e($salesperson->parishes->count()); ?>)</h2>
                <ul style="margin:0;padding-left:18px">
                    <?php $__currentLoopData = $salesperson->parishes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parish): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><a href="<?php echo e(route('panel.products.edit', $parish)); ?>"><?php echo e($parish->name); ?></a>
                            <?php if($parish->city): ?> <span class="text-muted">— <?php echo e($parish->city); ?></span> <?php endif; ?>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <div class="form-hint" style="margin-top:8px">
                    <a href="<?php echo e(route('panel.products.index', ['q' => $salesperson->name])); ?>">Pokaż w liście parafii →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/pay/unified/resources/views/storefront/common/panel/salespeople/form.blade.php ENDPATH**/ ?>