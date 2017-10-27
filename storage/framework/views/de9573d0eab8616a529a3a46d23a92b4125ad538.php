<?php $__env->startSection('content'); ?>

<h1>Processos administrativos</h1>
<p class="lead"> 
	<a href="<?php echo e(route('processosadms.create')); ?>">Adicionar</a> - 
	<a href="<?php echo e(route('processosadms.search')); ?>">Consultar</a> - 
	<a href="<?php echo e(route('processosadms.import')); ?>">Importar</a>
</p>
<hr>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>