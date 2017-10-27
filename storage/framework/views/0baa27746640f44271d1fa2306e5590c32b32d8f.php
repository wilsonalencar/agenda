<?php $__env->startSection('content'); ?>

<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<?php if(Session::has('alert')): ?>
    <div class="alert alert-danger">
         <?php echo Session::get('alert'); ?>

    </div>
   
<?php endif; ?>

<h1>Adicionar Mensageria - Processos adminstrativos</h1>
<hr>
<?php echo Form::open([
    'route' => 'mensageriaprocadms.store'
]); ?>


<div class="form-group">
    <div style="width:30%">
        <?php echo Form::label('role_id', 'Tipo De Usuário:', ['class' => 'control-label']); ?>

        <?php echo Form::select('role_id', $roles, null, array('class' => 'form-control')); ?>

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('parametro_qt_dias', 'Dias:', ['class' => 'control-label']); ?>

    <?php echo Form::text('parametro_qt_dias', null, ['class' => 'form-control', 'id' => 'parametro_qt_dias']); ?>

    </div>
</div>

<?php echo Form::submit('Cadastrar', ['class' => 'btn btn-default']); ?>


<?php echo Form::close(); ?>

<hr/>
<script>
jQuery(function($){
    $( "#role_id" ).change(function() { 
        if ($(this).val() == 0) {
            $("#parametro_qt_dias").val('');
            return false;
        }

        $.ajax(
        {
            type: "GET",
            url: '<?php echo e(url('mensageriaprocadms')); ?>/search_role',
            cache: false,
            async: false,
            dataType: "json",
            data:
            {
                'role_id':$(this).val()
            },
            success: function(d)
            {
                if (d.success) {
                   $("#parametro_qt_dias").val(d.data.parametro_qt_dias);
                   
                } else {
                    $("#parametro_qt_dias").val('');
                    
                }
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>