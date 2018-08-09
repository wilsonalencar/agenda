@extends('...layouts.master')

@section('content')
        <!--span>Prezado usuário, selecione a atividade a qual se refere a entrega:</span><br/><br/-->
        <table class="table table-bordered display" id="entregas-table">
            <thead>
                <tr>
                    <td colspan="10">
                        <div class="form-group">
                            <div class="col-xs-5 pull-left">
                                <input style="width: 145px; position:relative; left:10px; " placeholder="codigo" type="text" id="src_codigo" name="src_codigo" value="<?= $filter_codigo ?>">
                                <input style="width: 145px; position:relative; left:10px; " placeholder="cnpj" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
                                <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
                            </div>
                            <div class="col-xs-3 selectContainer pull-right">
                                <select class="form-control" id="src_status" name="src_status">
                                    <option <?= $filter_status=='T'?'selected':'' ?> value="T">Todas as entregas em aberto</option>
                                    <option <?= $filter_status=='A'?'selected':'' ?> value="A">Entregas em aprovação</option>
                                    <option <?= $filter_status=='E'?'selected':'' ?> value="E">Entregas não efetuadas</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>ID</th>
                    <th>DESCRIÇÃO</th>
                    <th>TRIBUTO</th>
                    <!--th>REF</th-->
                    <th>P.A.</th>
                    @if ( Auth::user()->hasRole('analyst'))
                    <th>VENCIMENTO</th>
                    @else
                    <th>ENTREGA</th>
                    @endif
                    <th>F.P.</th>
                    <th>CNPJ</th>
                    <th>COD</th>
                    <th></th>
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
                    url: "{!! route('entregas.data') !!}",
                    data: function (d) {
                        d.codigo = $('#src_codigo').val();
                        d.cnpj = $('#src_cnpj').val();
                        d.status_filter = $('#src_status option:selected').val();
                    }
                },
        columnDefs: [{ "width": "22%", "targets": 1 },{ "width": "120px", "targets": 2 },{ "width": "150px", "targets": 6 }],
        columns: [
            {data: 'id', name: 'id'},
            {data: 'descricao', name: 'descricao'},
            {data: 'regra.tributo.nome', name: 'regra.tributo.nome', searchable: false, orderable: false},
            //{data: 'regra.ref', name: 'regra.ref', orderable: false},
            {data: 'periodo_apuracao', name: 'periodo_apuracao'},
            @if ( Auth::user()->hasRole('analyst'))
            {data: 'limite', name: 'limite', render: function ( data ) {    return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            @else
            {data: 'data_entrega', name: 'data_entrega', render: function ( data ) {    if (data=='0000-00-00 00:00:00') return '-';
                                                                                        else return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            @endif
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
            {data: 'estemp.cnpj', name: 'estemp.cnpj',searchable: false, orderable: false, render: function (data) { if (data != undefined) { return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14); } else return '-'; } },
            {data: 'estemp.codigo', name: 'estemp.codigo',searchable: false, orderable: false, render: function (data) { if (data == undefined) { return '-'; } else { return data;}}},
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data, type, row) {

                                                    var url = '';
                                                    switch(row['status']) {
                                                        case 1:
                                                                url = '<a href="{{ route('upload.entrega', ':id_atividade') }}" style="margin-left:10px" class="btn btn-default btn-sm">Entregar</a>';
                                                                url = url.replace(':id_atividade', data);
                                                                break;

                                                        case 2:
                                                                @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                                                                url = '<a href="{{ route('entregas.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-danger btn-default btn-sm">Em aprovação</a>';
                                                                url = url.replace(':id_atividade', data);
                                                                @else
                                                                url = '<span style="margin-left:10px; cursor:not-allowed" class="btn btn-danger btn-default btn-sm">Em aprovação</span>';
                                                                @endif
                                                                break;
                                                        case 3:
                                                                url = '<a href="{{ route('entregas.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-success btn-default btn-sm">Recibo</a>';
                                                                url = url.replace(':id_atividade', data);
                                                                break;
                                                    }
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
            var val_status = $('#src_status option:selected').val();

            if (val_cnpj || val_codigo || val_status) {
                var url = "{{ route('entregas.index') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo.replace(/[^0-9]/g,'')+"&vst="+val_status;
            } else {
                var url = "{{ route('entregas.index') }}";
            }
            $("body").css("cursor", "progress");
            location.replace(url);
    });

    $('#src_status').change(function(){
        $("body").css("cursor", "progress");
        $('#adv_search').click();
    });

});
jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
    $('input[name="src_codigo"]').mask("9999");
});
</script>

@stop

