<?php $__env->startSection('content'); ?>

<?php if(Session::has('message')): ?>
   <div class="alert alert-info"><?php echo e(Session::get('message')); ?></div>
<?php endif; ?>
        <!--span>Prezado usuário, selecione a atividade a qual se refere a entrega:</span><br/><br/-->
        <table class="table table-bordered display" id="entregas-table">
            <thead>
                <tr>
                    <td colspan="10">
                        <input style="width: 145px; position:relative; left:10px; " placeholder="codigo" type="text" id="src_codigo" name="src_codigo" value="<?= $filter_codigo ?>">
                        <input style="width: 145px; position:relative; left:10px; " placeholder="cnpj" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
                        <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
                    </td>
                </tr>
                <tr>
                    <th>ID</th>
                    <th>DESCRIÇÃO</th>
                    <th>TRIBUTO</th>
                    <!--th>REF</th-->
                    <th>P.A.</th>
                    <!--th>DATA LIMITE</th-->
                    <th>DATA ENTREGA</th>
                    <th>F.P.</th>
                    <th>CNPJ</th>
                    <th>COD</th>
                    <th>DET.</th>
                    <th>ARQUIVO</th>
                </tr>
            </thead>
        </table>
<script>

$(function() {
    $('#entregas-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                    url: "<?php echo route('arquivos.data'); ?>",
                    data: function (d) {
                        d.codigo = $('#src_codigo').val();
                        d.cnpj = $('#src_cnpj').val();
                    }
                },
        columnDefs: [{ "width": "22%", "targets": 1 },{ "width": "120px", "targets": 2 },{ "width": "200px", "targets": 6 }],
        columns: [
            {data: 'id',name:'id'},
            {data: 'descricao', name: 'descricao'},
            {data: 'regra.tributo.nome', name: 'regra.tributo.nome', searchable: false, orderable: false},
            //{data: 'regra.ref', name: 'regra.ref', orderable: false},
            {data: 'periodo_apuracao', name: 'periodo_apuracao'},
            //{data: 'limite', name: 'limite', render: function ( data ) {    return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            {data: 'data_entrega', name: 'data_entrega', render: function ( data ) {    if (data=='0000-00-00 00:00:00') return '-';
                                                                                        else return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            {data: 'id', name: 'atraso', searchable: false, orderable: false, render: function (data, type, row) {
                                                                    var date1 = new Date(row['limite']);
                                                                    var date2 = new Date(row['data_entrega']);
                                                                    var timeDiff = (date2.getTime() - date1.getTime());
                                                                    var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
                                                                    var retval = "-";
                                                                    if (diffDays>1) {
                                                                      retval = diffDays+' dias';
                                                                    }
                                                                    return retval;
                                                                  }
            },
            {data: 'estemp.cnpj', name: 'estemp.cnpj',searchable: false, orderable: false, render: function (data) {return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14)} },
            {data: 'estemp.codigo', name: 'estemp.codigo',searchable: false, orderable: false },
            {data: 'id', name:'detalhe', searchable: false, orderable: false, render: function (data, type, row) {

                                                    var url = '';
                                                    url =  '<a href="<?php echo e(route('arquivos.show', ':id_atividade')); ?>" style="margin-left:10px" class="btn btn-success btn-default btn-sm">Detalhe</a>';
                                                    url = url.replace(':id_atividade', data);
                                                    return url;

            }},
            {data: 'id', name:'detalhe', searchable: false, orderable: false, render: function (data, type, row) {

                                                    var url = '';
                                                    url = '<a href="<?php echo e(url('download/')); ?>/'+data+'" style="margin-left:10px" class="btn btn-success btn-default btn-sm"><i class="fa fa-btn fa-cloud-download"></i></a>';
                                                    return url;

            }}

        ],
        order: [[ 4, "asc" ]],
        language: {
                            "searchPlaceholder": "ID, P.A. ou descrição"
                            //"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        aLengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    iDisplayLength: 10,
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
        ]
    });

    $('#adv_search').on('click', function(e) {
                var val_cnpj = $('#src_cnpj').val();
                var val_codigo = $('#src_codigo').val();
                if (val_cnpj || val_codigo) {
                    var url = "<?php echo e(route('arquivos.index')); ?>?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo.replace(/[^0-9]/g,'');
                } else {
                    var url = "<?php echo e(route('arquivos.index')); ?>";
                }
                $("body").css("cursor", "progress");
                location.replace(url);
    });

});
jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
    $('input[name="src_codigo"]').mask("9999");
});
</script>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>