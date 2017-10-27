<!doctype html>
<html lang="en">
<?php echo $__env->make('layouts.head', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
<body>

<?php echo $__env->make('layouts.nav', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<main>
    <div class="container">
        <?php if(session('status')): ?>
            <div class="alert alert-success">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>
        <?php if(session('seid') && !Auth::guest()): ?>
            <style>
                .navbar-brand {
                  padding: 0px; /* firefox bug fix */
                }
                .navbar-brand {
                  position:absolute; right: 50px; top:100px;
                  background: url("<?php echo e(URL::to('/')); ?>/assets/logo/logo-<?php echo e(\Illuminate\Support\Facades\Crypt::decrypt(session('seid'))); ?>.png") center / contain no-repeat;
                  width: 100px;
                }
            </style>
            <div class="navbar-brand"></div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</main>
</body>
</html>