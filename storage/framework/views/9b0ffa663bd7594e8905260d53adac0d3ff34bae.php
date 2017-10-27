<?php $__env->startSection('content'); ?>

<?php if(Session::has('alert')): ?>
    <div class="alert alert-danger">
         <?php echo Session::get('alert'); ?>

    </div>
   
<?php endif; ?>

<hr>
<?php echo Form::open([
    'route' => 'processosadms.action_import',
    'files'=>true,
    'id'=>'form_import', 
    'name' => 'form_import'
]); ?>



<div class="form-group">
    <div style="width:30%">
    <?php echo Form::label('import_csv', 'Enviar arquivo CSV para realização de import:', ['class' => 'control-label']); ?>

    <?php echo Form::file('file_csv', null, ['class' => 'form-control']); ?>

    </div>
</div>


<?php echo Form::button('Importar CSV', ['class' => 'btn btn-default', 'onClick' => "submit_form();"]); ?>

<a href="<?php echo e(route('processosadms.index')); ?>" class="btn btn-default">Voltar</a>

<?php echo Form::close(); ?>

<hr/>
<script>
    function submit_form()
    {
        var formData = new FormData($(document.forms[0])[0]);       
        $.ajax({
            type: 'POST',
            headers:
            {
                'X-CSRF-Token': $('input[name="_token"]').val()
            },
            url: 'action_valid_import',
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(d)
            {
                if (d.success) {
                    
                    if (d.dataApuracaoDiferente) {

                        if (confirm('Existem registros com períodos de apuração diferentes, deseja continuar com a importação?')) {
                            form_import.submit();
                        } else {
                            location.replace('/movtocontacorrentes/import');
                        }

                    } else {
                        form_import.submit();
                    }

                } else {
                    alert(d.mensagem);
                }
            }
        });
    }


</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>