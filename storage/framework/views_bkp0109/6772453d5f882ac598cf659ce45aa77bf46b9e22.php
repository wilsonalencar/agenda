<?php $__env->startSection('content'); ?>

<h1>Dashboard Cargas</h1>

<div class="row">
    <div class="col-md-6">
        <div id="graph_container_1" style="height: 300px">Entrada</div>
    </div>
    <div class="col-md-6">
        <div id="graph_container_2" style="height: 300px">Saida</div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div id="graph_container_3" style="height: 350px">Consolidado</div>
    </div>
</div>
<script>

var cargas_entrada = <?php echo e($graph_data['E']); ?>;
var cargas_saida = <?php echo e($graph_data['S']); ?>;
var cargas_completa = <?php echo e($graph_data['C']); ?>;
var cargas_totais = <?php echo e($graph_data['T']); ?>;

$('#graph_container_1').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
                text: 'Status Cargas (Entrada)'

        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_entrada,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_entrada,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});

$('#graph_container_2').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
                text: 'Status Cargas (Saida)'

        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_saida,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_saida,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});

$('#graph_container_3').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
                text: 'Status Cargas (Consolidado)'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_completa,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_completa,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>