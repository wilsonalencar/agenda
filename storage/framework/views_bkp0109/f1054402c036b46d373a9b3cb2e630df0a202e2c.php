<?php $__env->startSection('content'); ?>

<h1>REGRA</h1>
<p class="lead">TRIBUTO: <?php echo e($tributo->nome); ?></p>
<p class="lead">NOME ESPECIFICO: <?php echo e($regra->nome_especifico); ?></p>
<p class="lead">REFERÊNCIA: <?php echo e($regra->ref); ?></p>
<p class="lead">ADIANTAMENTO ENTREGA NO FIM SEMANA: <?php echo e($regra->afds?'SIM':'NÃO'); ?></p>
<hr>
<p>PROXIMA(S) ENTREGA(S) PREVISTA(S):</p>
<?php foreach($entregas as $entrega): ?>
<b><?php echo e(substr($entrega['data'],0,10)); ?></b><?php echo e(' ('.$entrega['desc'].')'); ?>

<br/>
<?php endforeach; ?>
<br/>
<?php if($empresas): ?>
    <div style="margin-left:20px; padding-bottom: 20px" class="row">
        EMPRESAS:
    </div>
    <?php foreach($empresas as $empresa): ?>
    <div class="row">
        <div class="col-md-2">
            <a href="<?php echo e(route('empresas.show', $empresa->id)); ?>" style="margin-left:10px" class="btn btn-default btn-sm"><?php echo e(mask($empresa->cnpj,'##.###.###/####-##')); ?></a>
        </div>
        <div class="col-md-2">
            <?php echo e(' CODIGO: '.$empresa->codigo); ?>

        </div>
         <div class="col-md-2">
            <?php echo e($empresa->nome.' ('.$empresa->uf.') '); ?>

        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<br/><br/>
<?php if($estabs): ?>
    <div style="padding-bottom: 20px" class="row">
        ESTABELECIMENTOS:
    </div>
    <?php foreach($estabs as $estab): ?>
    <div class="row">
        <div class="col-md-2">
            <a href="<?php echo e(route('estabelecimentos.show', $estab->id)); ?>" style="margin-left:10px" class="btn btn-default btn-sm"><?php echo e(mask($estab->cnpj,'##.###.###/####-##')); ?></a>
        </div>
        <div class="col-md-2">
            <?php echo e(' CODIGO: '.$estab->codigo); ?>

        </div>
        <div class="col-md-2">
            <?php echo e($estab->nome.' ('.$estab->uf.') '); ?>

        </div>
        <div class="col-md-2">
            <?php if(in_array($estab->id,$blacklist)): ?>
                <a href="<?php echo e(route('regras.setBlacklist', array($regra->id,$estab->id,0))); ?>" style="color:red; padding-left:50px">INATIVO</a>
            <?php else: ?>
                <a href="<?php echo e(route('regras.setBlacklist', array($regra->id,$estab->id,1))); ?>" style="color:green; padding-left:50px">ATIVO</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<hr>
<div class="row">
    <div class="col-md-6">
        <a href="<?php echo e(route('regras.index')); ?>" class="btn btn-default">Voltar para todas as regras</a>
        <a href="<?php echo e(route('calendario')); ?>" class="btn btn-default">Voltar para Calendario</a>
    </div>
    <div class="col-md-6 text-right">
        <?php echo Form::open([
            'method' => 'DELETE',
            'route' => ['regras.destroy', $regra->id]
        ]); ?>

            <?php echo Form::submit('Cancelar esta regra?', ['class' => 'btn btn-default']); ?>

        <?php echo Form::close(); ?>

    </div>
</div>
<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });

</script>
<?php $__env->stopSection(); ?>

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

?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>