<?php $__env->startSection('content'); ?>

<h2><?php echo e($user->name); ?></h2>
<hr>
<?php if( Auth::user()->hasRole('owner') || !$user->hasRole('owner')): ?>
<?php echo Form::model($user, [
    'method' => 'PATCH',
    'route' => ['usuarios.update', $user->id]
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('name', 'Nome:', ['class' => 'control-label']); ?>

    <?php echo Form::text('name', null, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('email', 'E-Mail:', ['class' => 'control-label']); ?>

    <?php echo Form::text('email', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('multiple_select_tributos[]', 'Responsabilidade Tributos', ['class' => 'control-label'] ); ?>

    <?php echo Form::select('multiple_select_tributos[]', $tributos, $user->tributos()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']); ?>

    </div>
</div>

<?php echo Form::submit('Atualiza Usuário', ['class' => 'btn btn-default']); ?>


<?php echo Form::close(); ?>


<script type="text/javascript">
  $('select').select2();
</script>
<?php endif; ?>
<a href="<?php echo e(route('usuarios.index')); ?>" class="btn btn-default">Voltar</a>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>