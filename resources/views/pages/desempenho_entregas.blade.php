@extends('...layouts.graficos')
@section('content')

{!! Form::open([
    'route' => 'graficos'
]) !!}

<?php 
    $displayLink    = 'none';
    $displayCombo   = 'block';
    if (!empty($empresas_selecionadas)) {
        $displayLink    = 'block';
        $displayCombo   = 'none';
    }
?>

<div class="graficos">

    <div class="grafico-content">
        <div class="row">
            
            <div class="col-md-6">
                <div class="card">
                    <div class="header-grafh yellow">
                        Nome da empresa  <img src="/assets/logo/Logo-1.png" align="right" style="margin-top: -2px;" height="22px">
                    </div>
                    <div id="container_gauge" style="height:367px">Gauge</div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="header-grafh red">
                        Nome da empresa  <img src="/assets/logo/Logo-4.png" align="right" style="margin-top: -2px;" height="22px">
                    </div>
                    <div id="container_gauge" style="height:367px">Gauge</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="header-grafh blue">
                        Nome da empresa  <img src="/assets/logo/Logo-5.png" align="right" style="margin-top: -2px;" height="22px">
                    </div>
                    <div id="container_gauge" style="height:367px">Gauge</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="header-grafh">
                        Nome da empresa  <img src="/assets/logo/Logo-6.png" align="right" style="margin-top: -2px;" height="22px">
                    </div>
                    <div id="container_gauge" style="height:367px">Gauge</div>
                </div>
            </div>

        </div>
    </div>


    <!-- <iframe src="" id="frameGrafico" width="49.75%" height="25%" scrolling="no" frameborder="0" style="border:0; background: #eceff3; min-height: 440px;">
    </iframe>

    <iframe src="" id="frameGrafico2" width="49.75%" height="25%" scrolling="no" frameborder="0" style="border:0; background: #eceff3; min-height: 440px;">
    </iframe>

    <iframe src="" id="frameGrafico3" width="49.75%" height="25%" scrolling="no" frameborder="0" style="border:0; background: #eceff3; min-height: 440px;">
    </iframe>

    <iframe src="" id="frameGrafico4" width="49.75%" height="25%" scrolling="no" frameborder="0" style="border:0; background: #eceff3; min-height: 440px;">
    </iframe> -->
    
</div>

<script type="text/javascript">
  
  var arrayFromPHP = <?php echo json_encode($empresas_selecionadas) ?>;
  var chamadas = 0;   
  count = 0;
  setInterval(function(){
      $.each(arrayFromPHP, function (i, elem) {
         if (count == 0) {
            AjaxFunctionGrafico(i, elem)
         } else {
            doTimeOut(i, elem);   
         }
         count++;
      });
    },1000);
  
  
  
  function doTimeOut (key, array) {
    chamadas++;
    setTimeout(function() {
        AjaxFunctionGrafico(key, array)
    }, chamadas*15000);
  }

  function AjaxFunctionGrafico(key, array)
  { 

    var iframe = document.getElementById('frameGrafico');
    iframe.src = '';
    var iframe = document.getElementById('frameGrafico2');
    iframe.src = '';
    var iframe = document.getElementById('frameGrafico3');
    iframe.src = '';
    var iframe = document.getElementById('frameGrafico4');
    iframe.src = '';


    if (array[0].key != undefined) {
        var iframe = document.getElementById('frameGrafico');
        iframe.src = 'dashboard?layout=entregometro&cor=yellow&emp_id='+array[0].key;
    }

    if (array[1].key != undefined) {
        var iframe = document.getElementById('frameGrafico2');
        iframe.src = 'dashboard?layout=entregometro&cor=red&emp_id='+array[1].key;
    }

     if (array[2].key != undefined) {
        var iframe = document.getElementById('frameGrafico3');
        iframe.src = 'dashboard?layout=entregometro&cor=blue&emp_id='+array[2].key;
    }

     if (array[3].key != undefined) {
        var iframe = document.getElementById('frameGrafico4');
        iframe.src = 'dashboard?layout=entregometro&cor=black&emp_id='+array[3].key;
    } 
     
  }

</script>
@stop

