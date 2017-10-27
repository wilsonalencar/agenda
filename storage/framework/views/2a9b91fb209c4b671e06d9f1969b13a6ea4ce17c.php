<?php $__env->startSection('content'); ?>

<h1>Movimento - Conta Corrente</h1>
<p class="lead"> 
	<a href="<?php echo e(route('movtocontacorrentes.create')); ?>">Adicionar</a> - 
	<a href="<?php echo e(route('movtocontacorrentes.search')); ?>">Consultar</a> - 
	<a href="<?php echo e(route('movtocontacorrentes.import')); ?>">Importar</a>
</p>
<hr>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>