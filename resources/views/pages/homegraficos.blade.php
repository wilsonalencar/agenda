@extends('layouts.graficos')

@section('content')


@if (!Auth::guest())

    <div>
        <div class="card">
            <div class="header-grafh">
                Status geral das entregas
            </div>
            <div id="graph_container" style="height: 50%">dashboard</div>
        </div>
    </div>

<script>
$(function () {
//Dashboard Graph
    var tot_status_1 = {{ ($graph['status_1']) }};
    var tot_status_2 = {{ ($graph['status_2']) }};
    var tot_status_3 = {{ ($graph['status_3']) }};
    var tot = tot_status_1+tot_status_2+tot_status_3;

    $('#graph_container').highcharts({
        chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                        text: ''

                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Entregas (efet./total): <b>{point.y} / {point.total}</b>'
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
                    name: 'Percentual entregas',
                    colorByPoint: true,
                    data: [{
                        name: 'Não efetuada',
                        y: tot_status_1, 
                        color: '#5268ff'
                    }, {
                        name: 'Em aprovação',
                        y: tot_status_2,
                        sliced: true,
                        selected: true
                    }, {
                        name: 'Aprovada',
                        y: tot_status_3
                    }]
                }]
    });
//Dashboard Messages
    $("#btn_open_vencidas").click(function(){

        $("#limit_vencidas").animate({
            height: $("#vencidas").height()
        },{{sizeof($vencidas)*10}});

        $("#btn_open_vencidas").css("display", "none");

        $("#btn_close_vencidas").css("display", "block");
    
    });

    $("#btn_close_vencidas").click(function(){

        $("#limit_vencidas").animate({
            height: 94
        },100);

        $("#btn_open_vencidas").css("display", "block");

        $("#btn_close_vencidas").css("display", "none");

    });

    $("#btn_open_urgentes").click(function(){

        $("#limit_urgentes").animate({
            height: $("#urgentes").height()
        },{{sizeof($urgentes)*10}});
    });

    $("#btn_close_urgentes").click(function(){

        $("#limit_urgentes").animate({
            height: 75
        },100);
    });

    $("#btn_open_aprovacao").click(function(){

            $("#limit_aprovacao").animate({
                height: $("#aprovacao").height()
            },{{sizeof($aprovacao)*10}});

            $("#btn_open_aprovacao").css("display", "none");

             $("#btn_close_aprovacao").css("display", "block");
        });

    $("#btn_close_aprovacao").click(function(){

        $("#limit_aprovacao").animate({
            height: 94
        },100);

        $("#btn_open_aprovacao").css("display", "block");

        $("#btn_close_aprovacao").css("display", "none");

    });

    $("#btn_open_vencimento").click(function(){

                $("#limit_vencimento").animate({
                    height: $("#vencimento").height()
                },{{sizeof($messages)*10}});
            });

    $("#btn_close_vencimento").click(function(){

        $("#limit_vencimento").animate({
            height: 75
        },100);
    });
});
//Spinner
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
              $('.spinner input').val('loading..');

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#btn_atualiza" ).click();

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
              $('.spinner input').val('loading..');

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#btn_atualiza" ).click();
       });
})(jQuery);

//Loading
$( "#btn_dashboard" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#btn_dashboard_analista" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#btn_atualiza" ).click(function() {
 $("body").css("cursor", "progress");
});

//BS Tree
$(function () {
    $('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
    $('.tree li.parent_li > span').on('click', function (e) {
        var children = $(this).parent('li.parent_li').find(' > ul > li');
        if (children.is(":visible")) {
            children.hide('fast');
            $(this).attr('title', 'Expand this branch').find(' > i').addClass('icon-plus-sign').removeClass('icon-minus-sign');
        } else {
            children.show('fast');
            $(this).attr('title', 'Collapse this branch').find(' > i').addClass('icon-minus-sign').removeClass('icon-plus-sign');
        }
        e.stopPropagation();
    });
});

</script>
@endif

@stop
<footer>
    @if (!Auth::guest())
        @include('layouts.footer-left')
    @else
        @include('layouts.footer')
    @endif
</footer>

<?php
function mask($val, $mask)
{
     $maskared = '';
     $k = 0;
     for($i = 0; $i<=strlen($mask)-1; $i++)
     {
     if($mask[$i] == '#')
     {
     if(isset($val[$k]))
     $maskared .= $val[$k++];
     }
     else
     {
     if(isset($mask[$i]))
     $maskared .= $mask[$i];
     }
     }
     return $maskared;
}
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}
?>


