@extends('...layouts.master')

@section('content')
<?php 
    $data = date('d/m/Y H:i:s');
?>
<h1>Movto - Conta Corrente</h1>
<p class="lead">Segue consulta de cadastros realizados. <a href="{{ route('movtocontacorrentes.create') }}">Adicionar outro?</a></p>
<hr>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Gráfico - Conta Corrente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div id="container" style="min-width: 550px; height: 400px; margin: 0 auto"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<table class="table table-bordered display" id="movtocontacorrentes-table" style="width: 100%; height: 100%; font-size: 12px;">
    <thead>
    <tr>
        <td colspan="10">
            <input style="width: 145px; position:relative; left:10px; " placeholder="Período" type="text" id="src_periodo" name="src_periodo" value="<?= $filter_periodo ?>">
            <input style="width: 145px; position:relative; left:10px; " placeholder="CNPJ" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
            <input style="width: 145px; position:relative; left:10px; " placeholder="Area" type="text" id="src_area" name="src_area" value="<?= $filter_area ?>">
            <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
            <button id="btn_grafico" style="position:relative; left:10px;">GRÁFICO</button>
        </td>
    </tr>
    <tr>
        <th>PERÍODO</th>
        <th>AREA</th>
        <th>CNPJ</th>
        <th>I.E</th>
        <th>CIDADE</th>
        <th>UF</th>
        <th>GUIA</th>
        <th>GIA</th>
        <th>SPED</th>
        <th>DIPAM</th>
        <th>DIF</th>
        <th></th>
    </tr>
    </thead>
</table>

<?php 
    $categoria = array();
    $dataDif = array();
    $dataNDif = array();
    $dataDifString = '';
    $dataNDifString = '';
    if (count($graphs) > 0) {

        foreach ($graphs as $el) {
            $categoria[] = $el->uf;
            $dataDif[] = $el->diferenca;
            $dataNDif[] = $el->s_diferenca;
        }
    }

    $dataDifString = implode(",", $dataDif);
    $dataNDifString = implode(",", $dataNDif);
?>
<script>
function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
function mascaraValor(valor) {
    valor = valor.toString().replace(/\D/g,"");
    valor = valor.toString().replace(/(\d)(\d{8})$/,"$1.$2");
    valor = valor.toString().replace(/(\d)(\d{5})$/,"$1.$2");
    valor = valor.toString().replace(/(\d)(\d{2})$/,"$1,$2");
    return valor                    
}

function dataHora()
{
    var now = new Date();
    return now.format("dd/MM/yyyy HH:mm:ss");
}

var graph_categories = [<?= "'" . implode("','", $categoria) . "'" ?>];

$(function() {

    Highcharts.chart('container', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'Status de conta corrente OK / Diferença'
    },
    xAxis: {
        categories: graph_categories
    },
    yAxis: {
            min: 0,
            max: 100,
            title: {
                text: 'Total (%) entregas'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
    legend: {
            align: 'right',
            x: 0,
            verticalAlign: 'top',
            y: 25,
            //floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: false
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },

    plotOptions: {
        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
        column: {
            stacking: 'percent',
            dataLabels: {
                enabled: true,
                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                style: {
                    textShadow: '0 0 5px black'
                }
            }
        },
    },
    series: [{
        name: 'OK',
        color: '#55BF3B',
        data: [<?php echo $dataNDifString; ?>]
    }, {
        name: 'Diferença',
        color: '#FC6F6F',
        data: [<?php echo $dataDifString; ?>]
    }]
});


    $('#movtocontacorrentes-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('movtocontacorrentes.data') !!}",
                data: function (d) {
                    d.area = $('#src_area').val();
                    d.cnpj = $('#src_cnpj').val();
                    d.periodo = $('#src_periodo').val();
                }
        },
        columns: [
            {data: 'periodo_apuracao', name: 'periodo_apuracao'},
            {data: 'estabelecimentos.codigo', name: 'estabelecimentos.codigo'},
            {data: 'estabelecimentos.cnpj', name: 'estabelecimentos.cnpj',render: function ( data ) {
                                                      return printMask(data);
                                                    }},
            {data: 'estabelecimentos.insc_estadual', name: 'estabelecimentos.insc_estadual'},
            {data: 'estabelecimentos.municipio.nome', name: 'estabelecimentos.municipio.nome'},
            {data: 'estabelecimentos.municipio.uf', name: 'estabelecimentos.municipio.uf'},
            {data: 'vlr_guia', name: 'vlr_guia',render: function ( data ) {
                                                      return mascaraValor(data);
                                                    }},
            {data: 'vlr_gia', name: 'vlr_gia',render: function ( data ) {
                                                      return mascaraValor(data);
                                                    }},
            {data: 'vlr_sped', name: 'vlr_sped',render: function ( data ) {
                                                      return mascaraValor(data);
                                                    }},
            {data: 'dipam', name: 'dipam', searchable: false, render: function (data) {
                   if (data == 'S/M') {
                        return data;
                   }else{
                        return mascaraValor(data);
                   }
               }
            },
            {data: 'diferenca', name: 'diferenca', searchable: false, render: function (data) {
                   if (data == 0) {
                     return '<font color="red">X</font>'; 
                   }else{
                      return '<font color="green">OK</font>';
                   }
               }
            },
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                var url = '<a href="{{ route('movtocontacorrentes.edit', ':id_edit') }}" class="btn btn-default btn-sm">Editar</a>';
                url += '<a href="{{ route('movtocontacorrentes.delete', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">X</a>';
                url = url.replace(':id_edit', data);
                url = url.replace(':id_show', data);
                return url;
            }},

        ],
        "columnDefs": [
            { "width": "1%", "targets": 0 },
            { "width": "1%", "targets": 1 },
            { "width": "15%", "targets": 2 },
            { "width": "5%", "targets": 3 },
            { "width": "5%", "targets": 4 },
            { "width": "1%", "targets": 5 },
            { "width": "10%", "targets": 6, className: "dt-right"},
            { "width": "10%", "targets": 7, className: "dt-right"},
            { "width": "10%", "targets": 8, className: "dt-right"},
            { "width": "10%", "targets": 9, className: "dt-right"},
            { "width": "1%", "targets": 10 },
            { "width": "12%", "targets": 11 }
        ],
        language: {
            //"searchPlaceholder": "ID, P.A. ou descrição",
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
           
             {
                extend: 'excelHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                }
             },
            
             {
                extend: 'csvHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                }
             },

             {
                extend: 'copyHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                }
             },
             {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
                },
                customize: function(doc) {
                  //pageMargins [left, top, right, bottom] 
                  
                  doc.pageMargins = [ 180, 20, 120, 20 ];
                  doc.defaultStyle.alignment = 'right';
               },
                message: '<?=$data;?>',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Movimentação Conta Corrente',
                header: 'Movimentação Conta Corrente'
            }
        ],
        lengthMenu: [[10, 25, 100, -1], [10, 25, 100, "All"]]

    });
    
    
    $('#btn_grafico').on('click', function(e) {
        $('#myModal').modal();
    });

    $('#adv_search').on('click', function(e) {

        var val_cnpj        = $('#src_cnpj').val();
        var val_area        = $('#src_area').val();
        var val_periodo     = $('#src_periodo').val();

        if (val_cnpj || val_area || val_periodo) {
            var url = "{{ route('movtocontacorrentes.search') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_area+"&vcp="+val_periodo;
        } else {
            var url = "{{ route('movtocontacorrentes.search') }}?clear=s";
        }
        $("body").css("cursor", "progress");
        location.replace(url);
    });

});

jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
    $('input[name="src_periodo"]').mask("99/9999");
});

</script>
@stop
