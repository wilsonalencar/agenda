<?php $__env->startSection('content'); ?>

<h1><?php echo e($user->name); ?></h1>
E-MAIL:<p class="lead"><?php echo e($user->email); ?></p>

<hr/>
<div class="row">
    <div class="col-md-6">
        <a href="<?php echo e(route('usuarios.index')); ?>" class="btn btn-default">Voltar</a>
        <a href="<?php echo e(route('usuarios.edit', $user->id)); ?>" class="btn btn-default">Alterar Usuário</a>
        <a href="<?php echo e(route('usuarios.sendEmailReminder', $user->id)); ?>" class="btn btn-default">Test E-Mail Reminder</a>
    </div>
    <?php if( Auth::user()->hasRole('owner')): ?>
    <div class="col-md-6 text-right">
        <?php echo Form::open([
            'method' => 'DELETE',
            'route' => ['usuarios.destroy', $user->id]
        ]); ?>

            <?php echo Form::submit('Cancelar este usuário?', ['class' => 'btn btn-default']); ?>

        <?php echo Form::close(); ?>

    </div>
    <?php endif; ?>
</div>
<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>