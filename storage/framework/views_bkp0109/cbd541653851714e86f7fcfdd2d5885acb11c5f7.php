<?php $__env->startSection('content'); ?>

    <?php echo $calendar->calendar(); ?>

    <?php echo $calendar->script(); ?>


<?php $__env->stopSection(); ?>
<footer>
    <?php echo $__env->make('layouts.footer', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
</footer>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>