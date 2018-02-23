@extends('...layouts.master')

@section('content')

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Consulta conta corrente</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh blue">
                Conta Corrente Por Divergência em Andamento
            </div>
            <div id="graph_container_1" style="height: 300px">Entrada</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh red">
                Conta Corrente Por UF
            </div>
            <div id="graph_container_2" style="height: 300px">Saida</div>
        </div>
    </div>
</div>

<div class="row mgT30">
    <div class="col-md-12">
        <div class="card">
            <div class="header-grafh">
                Conta Corrente Por Status
            </div>
            <div id="graph_container_3" style="height: 350px">Consolidado</div>
        </div>
    </div>
</div>

<!-- Primeiro gráfico -->
<?php
if (!empty($graph1)) {
    
    $dadosGraph1 = json_decode($graph1, true);
    
    foreach ($dadosGraph1 as $var => $dadosArray) {
        $dadosGrafico[$dadosArray['periodo_apuracao']][] = $dadosArray;
    }    

    $periodos = '';
    $guiaxsped = '';
    $giaxsped = '';
    $guiaxgia = '';
    $guiaxdipam = '';
    $giaxdipam = '';
    $spedxdipam = '';

    foreach ($dadosGrafico as $periodo_apuracao => $dados) {
        foreach ($dados as $key) {
            $periodos   .= "'".$periodo_apuracao."',";
            $guiaxsped  .= $key['GUIASPED'].",";
            $giaxsped   .= $key['GIASPED'].",";
            $guiaxgia   .= $key['GUIAGIA'].",";
            $guiaxdipam .= $key['GUIADIPAM'].","; 
            $giaxdipam  .= $key['GIADIPAM'].",";
            $spedxdipam .= $key['SPEDDIPAM'].",";    
        }
    }

    $periodos = substr($periodos,0,-1);
    $guiaxsped = substr($guiaxsped,0,-1);
    $giaxsped = substr($giaxsped,0,-1);
    $guiaxgia = substr($guiaxgia,0,-1);
    $guiaxdipam = substr($guiaxdipam,0,-1);
    $giaxdipam = substr($giaxdipam,0,-1);
    $spedxdipam = substr($spedxdipam,0,-1);
    echo "<pre>";
    print_r($giaxdipam);
    echo "</pre>";exit;
}
?>

<script>

$('#graph_container_1').highcharts({
    chart: {
        type: 'bar'
    },
    title: {
        text: ''
    },
    xAxis: {
        categories: [<?php echo $periodos; ?>]
    },
    yAxis: {
        min: 0,
        title: {
            text: ''
        }
    },
    legend: {
        reversed: true
    },
    plotOptions: {
        series: {
            stacking: 'normal'
        }
    },

    series: [{
        name: 'GUIA x SPED',
        data: [<?php echo ($guiaxsped > 0 ? $guiaxsped : "" ); ?>]
    }, {
        name: 'GIA x SPED',
        data: [<?php echo ($giaxsped > 0 ? $giaxsped : "" ); ?>]
    }, {
        name: 'GUIA x GIA',
        data: [<?php echo ($guiaxgia > 0 ? $guiaxgia : "" ); ?>]
    }, {
        name: 'GUIA x DIPAM',
        data: [<?php echo ($guiaxdipam > 0 ? $guiaxdipam : "" ); ?>]
    }, {
        name: 'GIA x DIPAM',
        data: [<?php echo ($giaxdipam > 0 ? $giaxdipam : "" ); ?>]
    }, {
        name: 'SPED x DIPAM',
        data: [<?php echo ($spedxdipam > 0 ? $spedxdipam : "" ); ?>]
    }]
});


$('#graph_container_3').highcharts({
    title: {
        text: 'Solar Employment Growth by Sector, 2010-2016'
    },

    subtitle: {
        text: ''
    },

    yAxis: {
        title: {
            text: ''
        }
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },

    plotOptions: {
        series: {
            label: {
                connectorAllowed: false
            },
            pointStart: 2010
        }
    },

    series: [{
        name: 'Em Andamento',
        data: [1, 2, 3, 4, 5, 6, 7, 8]
    }, {
        name: 'Baixado',
        data: [1, 2, 6, 8, 6, 3, 2, 10]
    }], 

    responsive: {
        rules: [{
            condition: {
                maxWidth: 500
            },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                }
            }
        }]
    }
});
</script>
@stop
