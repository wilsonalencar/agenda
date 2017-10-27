<?php $__env->startSection('content'); ?>

<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<?php if(Session::has('alert')): ?>
    <div class="alert alert-danger">
         <?php echo Session::get('alert'); ?>

    </div>
   
<?php endif; ?>

<h1>Adicionar nova Conta Corrente</h1>
<hr>
<?php echo Form::open([
    'route' => 'movtocontacorrentes.store'
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']); ?>

    <?php echo Form::text('periodo_apuracao', $periodo_apuracao, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('area', 'Area:', ['class' => 'control-label']); ?>

    <?php echo Form::text('area', null, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    <?php echo Form::label('estabelecimento', 'Estabelecimento:', ['class' => 'control-label']); ?>

    <?php echo Form::text('estabelecimento', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    <?php echo Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    <?php echo Form::label('ie', 'Inscrição Estadual:', ['class' => 'control-label']); ?>

    <?php echo Form::text('ie', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>


<div class="form-group">
    <div style="width:40%">
    <?php echo Form::label('cidade', 'Cidade:', ['class' => 'control-label']); ?>

    <?php echo Form::text('cidade', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    <?php echo Form::label('uf', 'UF:', ['class' => 'control-label']); ?>

    <?php echo Form::text('uf', null, ['class' => 'form-control', 'readonly' => 'true']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('valor_guia', 'Valor Guia R$:', ['class' => 'control-label']); ?>

    <?php echo Form::text('vlr_guia', null, ['class' => 'form-control', 'id'=> 'vlr_guia']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('valor_gia', 'Valor Gia R$:', ['class' => 'control-label']); ?>

    <?php echo Form::text('vlr_gia', null, ['class' => 'form-control', 'id'=> 'vlr_gia']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('valor_sped', 'Valor Sped R$:', ['class' => 'control-label']); ?>

    <?php echo Form::text('vlr_sped', null, ['class' => 'form-control', 'id'=> 'vlr_sped']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('dipam', 'DIPAM:', ['class' => 'control-label']); ?>

    <?php echo Form::checkbox('dipam', 'S', false); ?>

   </div>
</div>

<div class="form-group" id="vlr_dipam_div" style="display: none">
    <div style="width:30%">
    <?php echo Form::label('vlr_dipam', 'Valor Dipam R$:', ['class' => 'control-label']); ?>

    <?php echo Form::text('vlr_dipam', null, ['class' => 'form-control', 'id'=> 'vlr_dipam']); ?>

    </div>
</div>


<?php echo Form::hidden('estabelecimento_id', null, ['class' => 'form-control', 'id'=> 'estabelecimento_id']); ?>

<?php echo Form::submit('Cadastrar', ['class' => 'btn btn-default']); ?>


<?php echo Form::close(); ?>

<hr/>

<script>
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");

    $("#vlr_guia, #vlr_sped, #vlr_gia, #vlr_dipam").maskMoney({symbol:'R$ ', allowZero:true,
            showSymbol:false, thousands:'.', decimal:',', symbolStay: false, defaultZero: true});

    $( "#area" ).change(function() { 
        $.ajax(
        {
            type: "GET",
            url: '<?php echo e(url('estabelecimento')); ?>/search_area',
            cache: false,
            async: false,
            dataType: "json",
            data:
            {
                'codigo_area':$(this).val()
            },
            success: function(d)
            {
                if (!d.success) {

                    alert('Código de Área não existe');
                    $("#estabelecimento").val('');
                    $("#estabelecimento_id").val('');
                    $("#cnpj").val('');
                    $("#ie").val('');
                    $("#cidade").val('');
                    $("#uf").val('');
                    $("#area").val('');
                    $("#area").focus();
                    return false;
                }       

                $("#estabelecimento").val(d.data.estabelecimento.razao_social);
                $("#estabelecimento_id").val(d.data.estabelecimento.id);
                $("#cnpj").val(printMask(d.data.estabelecimento.cnpj));
                $("#ie").val(d.data.estabelecimento.insc_estadual);
                $("#cidade").val(d.data.municipio.nome);
                $("#uf").val(d.data.municipio.uf);
            }
        });
    });    

    $( "#dipam" ).change(function() {
        if ($(this).is(':checked')) {
            $("#vlr_dipam_div").show();
        } else {
            $("#vlr_dipam_div").hide();
            $("#vlr_dipam").val('0');
        }
    });      
});

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
</script>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>