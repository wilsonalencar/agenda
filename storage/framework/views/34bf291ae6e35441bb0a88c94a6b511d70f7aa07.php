<?php $__env->startSection('content'); ?>

<h2><?php echo e($estabelecimento->razao_social); ?></h2>
<hr>
<?php echo Form::model($estabelecimento, [
    'method' => 'PATCH',
    'route' => ['estabelecimentos.update', $estabelecimento->id]
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('codigo', 'Codigo:', ['class' => 'control-label']); ?>

    <?php echo Form::text('codigo', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('razao_social', 'Razão Social:', ['class' => 'control-label']); ?>

    <?php echo Form::text('razao_social', null, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    <?php echo Form::label('endereco', 'Endereço:', ['class' => 'control-label']); ?>

    <?php echo Form::text('endereco', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:20%">
    <?php echo Form::label('num_endereco', 'Numero/Complemento:', ['class' => 'control-label']); ?>

    <?php echo Form::text('num_endereco', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <?php echo Form::hidden('empresa_id'); ?>

</div>
<div class="form-group">
    <div style="width:20%">
    <?php echo Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']); ?>

    <br/>
    <?php echo Form::select('cod_municipio', $municipios, null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        <?php echo Form::label('insc_estadual', 'Inscrição Estadual:', ['class' => 'control-label']); ?>

        <?php echo Form::text('insc_estadual', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        <?php echo Form::label('insc_municipal', 'Inscrição Municipal:', ['class' => 'control-label']); ?>

        <?php echo Form::text('insc_municipal', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
        <?php echo Form::label('data_cadastro', 'Data Cadastro', ['class' => 'control-label']); ?>

        <?php echo Form::date('data_cadastro', date('Y-m-d', strtotime($estabelecimento->data_cadastro)), ['class' => 'form-control','style' => 'width:200px']); ?>

</div>
<div class="form-group">
    <?php echo Form::label('ativo', 'Ativo?', ['class' => 'control-label']); ?>

    <?php echo Form::checkbox('ativo', 1, null,['class' => 'form-control','style' => 'width:30px']); ?>

</div>

<?php echo Form::submit('Update Estabelecimento', ['class' => 'btn btn-default']); ?>

<a href="<?php echo e(route('estabelecimentos.index')); ?>" class="btn btn-default">Voltar</a>

<?php echo Form::close(); ?>

<br/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>