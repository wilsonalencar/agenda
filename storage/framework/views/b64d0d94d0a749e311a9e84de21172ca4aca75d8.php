<?php $__env->startSection('content'); ?>


<hr>
<div class="row">
    <div class="col-md-7">
        <p class="lead"><b><?php echo e($empresa->razao_social); ?></b></p>
        <p class="lead">CODIGO: <?php echo e($empresa->codigo); ?></p>
        <p class="lead">CNPJ: <?php echo e(mask($empresa->cnpj,'##.###.###/####-##')); ?></p>
        <p class="lead">LOCAL: <?php echo e($empresa->municipio->nome); ?> (<?php echo e($empresa->municipio->uf); ?>) | <?php echo e($empresa->endereco); ?> <?php echo e($empresa->num_endereco); ?></p>
        <p class="lead">IE: <?php echo e($empresa->insc_estadual?$empresa->insc_estadual:'não cadastrado'); ?></p>
        <p class="lead">IM: <?php echo e($empresa->insc_municipal?$empresa->insc_municipal:'não cadastrado'); ?></p>
    </div>
    <div class="col-md-3  pull-right">
        <img style="max-height: 150px" src="<?php echo e(URL::to('/')); ?>/assets/logo/Logo-<?php echo e($empresa->id); ?>.png" />
        <img style="width:250px" src="<?php echo e(URL::to('/')); ?>/assets/img/img_empresa.png" />
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-8">
        <p style="" class="lead">Atividades em aberto relacionadas.</p>
        <?php if(sizeof($atividades)>0): ?>
        <div class="row">
            <div style="font-weight: bold" class="col-md-6">DESCRIÇÃO</div>
            <div style="font-weight: bold" class="col-md-2">PERIODO</div>
            <div style="font-weight: bold" class="col-md-2">ENTREGA</div>
            <div style="font-weight: bold" class="col-md-2"></div>
        </div>
        <?php endif; ?>
        <?php if(sizeof($atividades)==0): ?>
        <div class="row">
            <div class="col-md-6">Nenhuma atividade relacionada em aberto.</div>
        </div>
        <?php endif; ?>
        <?php foreach($atividades as $atividade): ?>
        <div class="row">
            <div class="col-md-6"><?php echo e($atividade['descricao']); ?></div>
            <div class="col-md-2"><?php echo e($atividade['periodo_apuracao']); ?></div>
            <div class="col-md-2"><?php echo e(Date_Converter($atividade['limite'])); ?></div>
            <div class="col-md-2"><a href="<?php echo e(route('atividades.show', $atividade['id'])); ?>" style="margin-left:10px" class="btn btn-default btn-xs">Abrir</a></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-10">
        <p style="" class="lead">Mapeamento tributos para esta empresa:</p>
        <div class="row">
        <?php foreach($empresa->tributos as $tributo): ?>
            <div class="col-md-3">
             <?php echo e($tributo->nome); ?>

            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-12">
        <p style="" class="lead">Mapeamento usuarios para esta empresa:</p>
        <div class="row">
        <?php foreach($empresa->users as $user): ?>
            <div class="col-md-4">
             <?php echo e($user->name); ?>

             <?php foreach($user->roles as $role): ?>
                (<?php echo e($role->display_name); ?>)
             <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<hr/>
<div class="panel panel-default">
        <div class="panel-heading">Painel Operacional para geração das atividades</div>
        <div style="padding:20px" class="panel-body">
            <div style="margin-bottom: 30px" class="row">
                <div class="col-xs-2 col-sm-2">
                    <label>Periodo Apuração: </label>
                    <input style="width: 80px; text-align: center" type="text" name="periodo" value="<?php echo e(date('mY')); ?>" />
                </div>
            </div>
            <div style="margin-left: 30px;" class="row">
                <?php echo e(Form::button('Gera todas as Atividades', array('id'=>'btn_geracao','class' => 'btn btn-default'))); ?>

            </div>
        </div>
        <div class="panel-footer clearfix">
            <div class="col-md-6">
                <a href="<?php echo e(route('empresas.index')); ?>" class="btn btn-default">Voltar</a>
                <a href="<?php echo e(route('empresas.edit', $empresa->id)); ?>" class="btn btn-default">Alterar Empresa</a>
            </div>
            <div class="col-md-6 text-right">
                <?php echo Form::open([
                    'method' => 'DELETE',
                    'route' => ['empresas.destroy', $empresa->id]
                ]); ?>

                    <?php echo Form::submit('Cancelar esta empresa?', ['class' => 'btn btn-default']); ?>

                <?php echo Form::close(); ?>

            </div>
        </div>
</div>

<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

        $('#btn_geracao').click(function() {

            var periodo = $('input[name="periodo"]').val();
            periodo = periodo.replace('/','');

            var url = '<?php echo e(url('empresa')); ?>/:periodo/:id_emp/geracao';
            url = url.replace(':periodo', periodo);
            url = url.replace(':id_emp', <?php echo e($empresa->id); ?>);

            location.replace(url);
        });

        jQuery(function($){
            $('input[name="periodo"]').mask("99/9999");
        });
    });
</script>
<?php
function mask($val, $mask)
{
     $maskared = '';
     $k = 0;
     for($i = 0; $i<=strlen($mask)-1; $i++)
     {
     if($mask[$i] == '#')
     {
     if(isset($val[$k]))
     $maskared .= $val[$k++];
     }
     else
     {
     if(isset($mask[$i]))
     $maskared .= $mask[$i];
     }
     }
     return $maskared;
}
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}
?>
<?php $__env->stopSection(); ?>







<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>