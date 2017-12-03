@extends('layouts.graficos')

@section('content')

@if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('supervisor'))

<hr/>
<div class="row hidden">
{!! Form::open([
    'route' => 'dashboard_tributo'
]) !!}
{!! Form::select('tributo', $tributos, ['class' => 'form-control'],['placeholder' => 'Seleciona um tributo...']) !!}
{!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
{!! Form::button('<i class="fa fa-bar-chart"></i> Detalhe entregas estad/municip', array('id'=>'dtrib_btn','class'=>'hidden btn btn-default', 'type'=>'submit')) !!}
{!! Form::close() !!}
</div>
<div class="row">
    <div id="container" style="height:69%" class="col-md-9">Dashboard</div>
    <div id="container_gauge" style="height:40%" class="col-md-3">Gauge</div>
</div>

<script>
<?php
    $array_entregue = array();
    $array_nentregue = array();
    $array_aprovacao = array();
    $array_entregue_vencidas = array();
    $array_nentregue_vencidas = array();
    $array_aprovacao_vencidas = array();

    foreach ($graph as $el) {
        $array_nentregue[] = isset($el['count']['s1'])?$el['count']['s1']:0;
        $array_aprovacao[] = isset($el['count']['s2'])?$el['count']['s2']:0;
        $array_entregue[] = isset($el['count']['s3'])?$el['count']['s3']:0;
        $array_nentregue_vencidas[] = isset($el['count']['v1'])?$el['count']['v1']:0;
        $array_aprovacao_vencidas[] = isset($el['count']['v2'])?$el['count']['v2']:0;
        $array_entregue_vencidas[] = isset($el['count']['v3'])?$el['count']['v3']:0;
    }

    $sum = array_map(function () {
        return array_sum(func_get_args());
    }, $array_entregue, $array_nentregue, $array_aprovacao,$array_entregue_vencidas,$array_nentregue_vencidas,$array_aprovacao_vencidas );

    $tot_entregas_efetuadas = array_sum($array_entregue) + array_sum($array_entregue_vencidas);
    $tot_entregas_periodo = $tot_entregas_efetuadas + array_sum($array_nentregue) + array_sum($array_aprovacao) + array_sum($array_nentregue_vencidas) + array_sum($array_aprovacao_vencidas);

    //Valor percentual
    for ($i=0; $i<sizeof($sum); $i++) {
        if ($sum[$i]>0) {
            $array_nentregue[$i] = round($array_nentregue[$i]/$sum[$i]*100,0);
            $array_aprovacao[$i] = round($array_aprovacao[$i]/$sum[$i]*100,0);
            $array_entregue[$i]  = round($array_entregue[$i]/$sum[$i]*100,0);
            $array_nentregue_vencidas[$i] = round($array_nentregue_vencidas[$i]/$sum[$i]*100,0);
            $array_aprovacao_vencidas[$i] = round($array_aprovacao_vencidas[$i]/$sum[$i]*100,0);
            $array_entregue_vencidas[$i]  = round($array_entregue_vencidas[$i]/$sum[$i]*100,0);
        }
    }

    $divisao = 0;
    if ($tot_entregas_periodo > 0) {
        $divisao = round(($tot_entregas_efetuadas*100)/$tot_entregas_periodo,2);
    }

    

?>
var graph_categories = [<?= "'" . implode("','", array_keys($graph)) . "'" ?>];
var graph_data = [[{{implode(',',$array_nentregue)}}],[{{implode(',',$array_aprovacao)}}],[{{implode(',',$array_entregue)}}],[{{implode(',',$array_nentregue_vencidas)}}],[{{implode(',',$array_aprovacao_vencidas)}}],[{{implode(',',$array_entregue_vencidas)}}]];

$(function () {

    setInterval(function(){ $( '#atualiza_btn' ).click() }, 300000);

    $.fn.bootstrapSwitch.defaults.onText = 'P.A.';
    $.fn.bootstrapSwitch.defaults.offText = 'D.E.';
    $("[name='pa-checkbox']").bootstrapSwitch();

    $('#container').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Status geral das entregas mensais'
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
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                    style: {
                        textShadow: '0 0 3px black'
                    }
                }
            },
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function () {
                            $("body").css("cursor", "progress");
                            var tributo = this.category;
                            $("select[name='tributo']").val(tributo);
                            $( "#dtrib_btn" ).click();
                        }
                    }
                }
            }
        },
        series: [{
            name: 'Não entregue',
            data: graph_data[0],
            color: '#DDDDDD'
        }, {
            name: 'Em aprovação',
            data: graph_data[1],
            color: Highcharts.getOptions().colors[0]
        }, {
            name: 'Entregue',
            data: graph_data[2],
            color: Highcharts.getOptions().colors[2]
        }, {
             name: 'Não entregue (f.p.)',
             data: graph_data[3],
             color: '#FC6F6F'
        }, {
             name: 'Em aprovação (f.p.)',
             data: graph_data[4],
             color: '#f2b44b'
        }, {
             name: 'Entregue (f.p.)',
             data: graph_data[5],
             color: '#F7F970'
         }]
    });

    $('#container_gauge').highcharts({

            chart: {
                type: 'gauge',
                plotBackgroundColor: null,
                plotBackgroundImage: null,
                plotBorderWidth: 0,
                plotShadow: false
            },
            title: {
                text: 'Entregômetro'
            },
            pane: {
                        startAngle: -150,
                        endAngle: 150,
                        background: [{
                            backgroundColor: {
                                //linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                                stops: [
                                    [0, '#FFF'],
                                    [1, '#333']
                                ]
                            },
                            borderWidth: 0,
                            outerRadius: '109%'
                        }, {
                            backgroundColor: {
                                //linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                                stops: [
                                    [0, '#333'],
                                    [1, '#FFF']
                                ]
                            },
                            borderWidth: 1,
                            outerRadius: '107%'
                        }, {
                            // default background
                        }, {
                            backgroundColor: '#DDD',
                            borderWidth: 0,
                            outerRadius: '105%',
                            innerRadius: '103%'
                        }]
            },
            // the value axis
            yAxis: {
                min: 0,
                max: 100,

                minorTickInterval: 'auto',
                minorTickWidth: 1,
                minorTickLength: 10,
                minorTickPosition: 'inside',
                minorTickColor: '#666',

                tickPixelInterval: 30,
                tickWidth: 2,
                tickPosition: 'inside',
                tickLength: 10,
                tickColor: '#666',
                labels: {
                    step: 2,
                    rotation: 'auto'
                },
                title: {
                    text: '% concluídas'
                },
                plotBands: [{
                    from: 80,
                    to: 100,
                    color: '#55BF3B' // green
                }, {
                    from: 60,
                    to: 80,
                    color: '#DDDF0D' // yellow
                }, {
                    from: 0,
                    to: 60,
                    color: '#DF5353' // red
                }]
            },

            series: [{
                name: 'Entregue: ',
                data: [{{$divisao}}],
                tooltip: {
                    valueSuffix: ' %'
                }
            }]

        },
        // Add some life
        function (chart) {
            if (false && !chart.renderer.forExport) {
                setInterval(function () {
                    var point = chart.series[0].points[0],
                        newVal,
                        inc = Math.round((Math.random() - 0.5) * 20);

                    newVal = point.y + inc;
                    if (newVal < 0 || newVal > 200) {
                        newVal = point.y - inc;
                    }

                    point.update(newVal);

                }, 3000);
            }
        });
});

(function ($) {
        $('.spinner .btn:first-of-type').on('click', function() { //UP
              var value = $('.spinner input').val();

              var mes = parseInt(value.substr(0,2));
              var year = parseInt(value.substr(3,4));
              mes += 1;
              if (mes>12) {
                  mes = 1;
                  year += 1;
              } else if (mes<10) {
                  mes = '0'+mes;
              }
              year = ''+year;
              $('.spinner input').val(mes+'/'+year);

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#atualiza_btn" ).click();

        });

       $('.spinner .btn:last-of-type').on('click', function() {  //DOWN
              var value = $('.spinner input').val();

              var mes = parseInt(value.substr(0,2));
              var year = parseInt(value.substr(3,4));
              mes -= 1;
              if (mes<1) {
                  mes = 12;
                  year -= 1;
              } else if (mes<10) {
                  mes = '0'+mes;
              }
              year = ''+year;
              $('.spinner input').val(mes+'/'+year);

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#atualiza_btn" ).click();
       });
})(jQuery);

$('input[type=radio][name=tipo_tributos]').on('change', function() {
    $("body").css("cursor", "progress");
    $( "#atualiza_btn" ).click();
});

$('input[name="pa-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

  $('input[name="switch_periodo"]').val(state?1:0);
  $("body").css("cursor", "progress");
  $( "#atualiza_btn" ).click();
});

//Loading
 $( "#btn_dashboard_analista" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#atualiza_btn" ).click(function() {
 $("body").css("cursor", "progress");
});
</script>

@endif

@stop
<footer>
   @include('layouts.footer')
</footer>
