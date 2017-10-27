<?php $__env->startSection('content'); ?>
<h1>TRIBUTO</h1>
<p class="lead">NOME: <?php echo e($tributo->nome); ?></p>
<p class="lead">DESCRIÇÃO: <?php echo e($tributo->descricao); ?> </p>
<p class="lead">CATEGORIA: <?php echo e($tributo->categoria->nome); ?> </p>
<hr>
<p>REGRAS ATIVAS:</p>
<?php foreach($regras as $regra): ?>
<a href="<?php echo e(url('regras').'/'.$regra->id); ?>"><?php echo e($tributo->nome.' '.$regra->nome_especifico.' - REF. '.$regra->ref); ?></a>
<br />
<?php endforeach; ?>
<br/>

<hr>
<div class="row">
    <div class="col-md-6">
        <a href="<?php echo e(route('tributos.index')); ?>" class="btn btn-default">Voltar para lista de tributos</a>
    </div>
    <div class="col-md-6 text-right">
        <?php echo Form::open([
            'method' => 'DELETE',
            'route' => ['tributos.destroy', $tributo->id]
        ]); ?>

            <?php echo Form::submit('Cancelar este tributo?', ['class' => 'btn btn-default']); ?>

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

<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>