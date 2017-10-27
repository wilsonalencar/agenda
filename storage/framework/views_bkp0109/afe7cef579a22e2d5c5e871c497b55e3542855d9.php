<?php $__env->startSection('content'); ?>

<h1>Usuários</h1>
<p class="lead">Segue a lista de todos os usuários cadastrados.</p>
<hr>
<table class="table table-bordered display" id="usuarios-table">
    <thead>
    <tr>
        <th>NOME</th>
        <th>E-MAIL</th>
        <th></th>
        <th></th>
    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#usuarios-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: "<?php echo route('usuarios.data'); ?>",
        columns: [
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'roles', name:'roles', searchable: false, orderable: false, render: function (data) {

                var html = '';

                if (data=='') {
                    html = '<img src="<?php echo e(URL::to('/')); ?>/assets/img/inactive-icon.png" title="Inactive user" />';
                } else {
                    $.each(data, function() {
                      var key = Object.keys(this)[1];
                      var value = this[key];
                      html += '<img src="<?php echo e(URL::to('/')); ?>/assets/img/'+value+'-icon.png" title="'+value+'" />';
                    });
                }
                 return html;
            }},
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                var url = '<a href="<?php echo e(route('usuarios.show', ':id_show')); ?>" class="btn btn-default btn-sm">Mostrar</a>';
                <?php if( Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin')): ?>
                url += '<a href="<?php echo e(route('usuarios.edit', ':id_edit')); ?>" style="margin-left:10px" class="btn btn-default btn-sm">Alterar</a>';
                url += '<a href="<?php echo e(route('usuarios.elevateRole', ':id_elevate')); ?>" style="margin-left:10px" class="btn btn-default btn-sm">Nivel Up (+)</a>';
                url += '<a href="<?php echo e(route('usuarios.decreaseRole', ':id_decrease')); ?>" style="margin-left:10px" class="btn btn-default btn-sm">Nivel Down (-)</a>';
                url = url.replace(':id_edit', data);
                url = url.replace(':id_elevate', data);
                url = url.replace(':id_decrease', data);
                <?php endif; ?>;
                url = url.replace(':id_show', data);
                return url;
            }}
        ],
        language: {
                                    //"searchPlaceholder": "ID, P.A. ou descrição",
                                    "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
         ]

    });

});

</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>