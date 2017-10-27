<?php $__env->startSection('content'); ?>

<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<h1>Adicionar nova empresa</h1>
<hr>
<?php echo Form::open([
    'route' => 'empresas.store'
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('codigo', 'código empresa:', ['class' => 'control-label']); ?>

    <?php echo Form::text('codigo', null, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cnpj', null, ['class' => 'form-control']); ?>

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
    <?php echo Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']); ?>

    <br/>
    <?php echo Form::select('cod_municipio', $municipios, ['class' => 'form-control']); ?>

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

<?php echo Form::submit('Cria nova empresa', ['class' => 'btn btn-default']); ?>


<?php echo Form::close(); ?>

<hr/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});
</script>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>