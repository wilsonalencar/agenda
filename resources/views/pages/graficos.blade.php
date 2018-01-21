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

    <div class="main" id="empresaMultipleSelectSelecionar" style="display: <?php echo $displayCombo ?>;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('multiple_select_empresas[]', 'Empresas', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-10">
                {!!  Form::select('multiple_select_empresas[]', $empresas, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
            <div class="col-md-2">
                {!! Form::submit('Selecionar', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
            
        </div>
    </div>
    <div id="linkEmpresaSelecionar" class="button-search" style="display: <?php echo $displayLink ?>;">
        <a href="">Nova Busca</a>
    </div>

    <iframe src="" id="frameGrafico" width=100% e height=1000px scrolling="no" frameborder="0" style="border:0"></iframe>
    
</div>

<script type="text/javascript">
  $('select').select2();
  
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
     if (typeof array.dashboard != 'undefined') {
        var iframe = document.getElementById('frameGrafico');
        iframe.src = 'home?layout=graficos&emp_id='+array.dashboard;
     } else {
        var iframe = document.getElementById('frameGrafico');
        iframe.src = 'dashboard?layout=graficos&emp_id='+array.gerencial;
     }
  }

</script>
@stop

