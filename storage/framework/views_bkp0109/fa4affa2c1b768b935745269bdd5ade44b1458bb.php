<?php $__env->startSection('content'); ?>

<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<h1>Adicionar novo estabelecimento</h1>
<hr><?php $empresas->prepend('Seleciona a empresa...'); ?>
<?php echo Form::open([
    'route' => 'estabelecimentos.store'
]); ?>

<div class="form-group">
    <?php echo Form::label('empresa_id', 'Empresa:', ['class' => 'control-label']); ?>

    <br/>
    <?php echo Form::select('empresa_id', $empresas, ['class' => 'form-control']); ?>

</div>
<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cnpj', null, ['class' => 'form-control']); ?>

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
    <?php echo Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']); ?>

    <br/>
    <?php echo Form::select('cod_municipio', $municipios, ['class' => 'form-control']); ?>

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

        <?php echo Form::date('data_cadastro', null, ['class' => 'form-control','style' => 'width:200px']); ?>

</div>
<a href="<?php echo e(route('estabelecimentos.index')); ?>" class="btn btn-default">Voltar</a>
<?php echo Form::submit('Cria novo estabelecimento', ['class' => 'btn btn-default']); ?>


<?php echo Form::close(); ?>


<br/>
<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");

});
$('select').on('change', function (e) {
   var optionSelected = $(this).find("option:selected");
   var valueSelected  = optionSelected.val();
   var textSelected   = optionSelected.text();
   $('#cnpj').attr('placeholder',textSelected.substring(0,8));
});
</script>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>