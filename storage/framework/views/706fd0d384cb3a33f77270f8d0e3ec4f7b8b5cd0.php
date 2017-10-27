<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<h2><?php echo e($regra->tributo->nome.' ('.$regra->ref.')'); ?></h2>
<hr>
<?php echo Form::model($regra, [
    'method' => 'PATCH',
    'route' => ['regras.update', $regra->id]
]); ?>

 <div class="form-group">
    <?php echo Form::label('tributo_id', 'Tributo:', ['class' => 'control-label']); ?>

    <?php echo Form::select('tributo_id', $tributos, null, ['style' => 'width:250px', 'class' => 'form-control']); ?>

</div>
<div class="row">
    <div class="col-md-3">
    <?php echo Form::label('nome_especifico', 'Nome Especifico:', ['class' => 'control-label']); ?>

    <?php echo Form::text('nome_especifico', null, ['class' => 'form-control']); ?>

    </div>
    <div class="col-md-3">
    <?php echo Form::label('ref', 'Referência:', ['class' => 'control-label']); ?>

    <?php echo Form::text('ref', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-md-3">
    <?php echo Form::label('regra_entrega', 'Regra entrega:', ['class' => 'control-label']); ?>

    <?php echo Form::text('regra_entrega', null, ['class' => 'form-control']); ?>

    </div>
    <div class="col-md-3">
    <?php echo Form::label('freq_entrega', 'Frequência (M/A):', ['class' => 'control-label']); ?>

    <?php echo Form::text('freq_entrega', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-lg-10">
    <?php echo Form::label('legislacao', 'Legislacao:', ['class' => 'control-label']); ?>

    <?php echo Form::text('legislacao', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-lg-10">
    <?php echo Form::label('obs', 'Observações:', ['class' => 'control-label']); ?>

    <?php echo Form::text('obs', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-md-3">
    <?php echo Form::label('afds', 'Adiantamento FDS?', ['class' => 'control-label']); ?>

    <?php echo Form::checkbox('afds', 1, null,['class' => 'form-control','style' => 'width:30px']); ?>

    </div>
    <div class="col-md-3">
    <?php echo Form::label('ativo', 'Ativo?', ['class' => 'control-label']); ?>

    <?php echo Form::checkbox('ativo', 1, null,['class' => 'form-control','style' => 'width:30px']); ?>

    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<?php echo Form::submit('Update Regra', ['class' => 'btn btn-default']); ?>

<a href="<?php echo e(route('regras.index')); ?>" class="btn btn-default">Voltar</a>

<?php echo Form::close(); ?>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>