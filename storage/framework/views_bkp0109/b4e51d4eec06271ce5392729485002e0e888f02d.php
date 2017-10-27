<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<h2><?php echo e($empresa->razao_social); ?></h2>
<hr>
<?php echo Form::model($empresa, [
    'method' => 'PATCH',
    'route' => ['empresas.update', $empresa->id]
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('codigo', 'Código:', ['class' => 'control-label']); ?>

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
    <div style="width:30%">
    <?php echo Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']); ?>

    <br/>
    <?php echo Form::select('cod_municipio', $municipios, null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        <?php echo Form::label('insc_municipal', 'Inscrição Municipal:', ['class' => 'control-label']); ?>

        <?php echo Form::text('insc_municipal', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        <?php echo Form::label('insc_estadual', 'Inscrição Estadual:', ['class' => 'control-label']); ?>

        <?php echo Form::text('insc_estadual', null, ['class' => 'form-control']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('multiple_select_tributos[]', 'Configuração Tributos', ['class' => 'control-label'] ); ?>

    <?php echo Form::select('multiple_select_tributos[]', $tributos, $empresa->tributos()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']); ?>

    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('multiple_select_users[]', 'Acesso Usuarios', ['class' => 'control-label'] ); ?>

    <?php echo Form::select('multiple_select_users[]', $users, $empresa->users()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']); ?>

    </div>
</div>

<?php echo Form::submit('Update Empresa', ['class' => 'btn btn-default']); ?>

<a href="<?php echo e(route('empresas.index')); ?>" class="btn btn-default">Voltar</a>

<?php echo Form::close(); ?>

<hr/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});

$('select').select2();

</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>