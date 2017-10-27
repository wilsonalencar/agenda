<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<?php if(Session::has('alert')): ?>
    <div class="alert alert-danger">
         <?php echo Session::get('alert'); ?>

    </div>
   
<?php endif; ?>


<div class="modal fade" id="myModalObservacao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Observações</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="observacaoHTML" style="width: 100%; height: 100%;">
        <?php foreach($observacoes as $observacao) { ?>
            <div class="row"><p><b><?php echo $observacao['nome']." - ".$observacao['data']."<p>". $observacao['descricao']; ?></b></p></div><br>
        <?php } ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<hr>
<?php echo Form::model($processosadms, [
    'method' => 'PATCH',
    'route' => ['processosadms.update', $processosadms->id]
]); ?>


<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']); ?>

    <?php echo Form::text('periodo_apuracao', $processosadms->periodo_apuracao, ['class' => 'form-control']); ?>

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
    <?php echo Form::label('nro_processo', 'Processo nro:', ['class' => 'control-label']); ?>

    <?php echo Form::text('nro_processo', $processosadms->nro_processo, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        <?php echo Form::label('responsavel_financeiro', 'Responsavel Financeiro:', ['class' => 'control-label']); ?>

        <?php echo Form::select('resp_financeiro_id', $respFinanceiro, $processosadms->resp_financeiro_id, array('class' => 'form-control')); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('resp_acompanhamento', 'Responsavel Acompanhamento:', ['class' => 'control-label']); ?>

    <?php echo Form::text('resp_acompanhamento', $processosadms->resp_acompanhamento, ['class' => 'form-control']); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        <?php echo Form::label('status', 'Status:', ['class' => 'control-label']); ?>

        <?php echo Form::select('status_id', $status, $processosadms->status_id, array('class' => 'form-control')); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        <?php echo Form::label('observacao', 'Observação:', ['class' => 'control-label']); ?>

        <?php echo Form::textarea('Observacao', '', null, array('class' => 'form-control')); ?>

    </div>
</div>


<?php echo Form::hidden('estabelecimento_id', null, ['class' => 'form-control', 'id'=> 'estabelecimento_id']); ?>



<?php echo Form::submit('Update Processo Administrativo', ['class' => 'btn btn-default']); ?>

<a href="javascript:void(0);" class="btn btn-default" onclick="getObservacao();">Observações</a>
<a href="<?php echo e(route('processosadms.search')); ?>" class="btn btn-default">Voltar</a>

<?php echo Form::close(); ?>

<hr/>
<script>
buscar_estabelecimento(0, $("#estabelecimento_id").val());
jQuery(function($){

    $( "#area" ).change(function() { 
        buscar_estabelecimento($(this).val(), 0);
    });          
});

function getObservacao()
{
    $("#myModalObservacao").modal();
}

function buscar_estabelecimento(codigo, id){
    $.ajax(
    {
        type: "GET",
        url: '<?php echo e(url('estabelecimento')); ?>/search_area',
        cache: false,
        async: false,
        dataType: "json",
        data:
        {
            'codigo_area':codigo,
            'estabelecimento_id':id 
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
            $("#area").val(d.data.estabelecimento.codigo);
        }
    });
}

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>