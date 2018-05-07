@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Geração de um cronograma de uma atividade</h1>
<hr>
{!! Form::open([
    'route' => 'cronogramaatividades.store'
]) !!}
<div class="form-group">
    {!! Form::label('multiple_select_empresas[]', 'Empresas', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_empresas[]', $empresas, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple', 'id' => 'multiple_emps']) !!}
</div>

<div class="form-group">
    {!! Form::label('multiple_select_estabelecimentos[]', 'Estabelecimentos', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_estabelecimentos[]', $estabelecimentos, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple', 'id' => 'multiple_estab']) !!}
</div>

<div class="form-group">
    {!! Form::label('select_tributos', 'Tributo (estabelecimento)', ['class' => 'control-label'] )  !!}
    {!!  Form::select('select_tributos', $tributos, array(), ['class' => 'form-control s2_multi', 'id' => 'select_tributo']) !!}
</div>

<div class="form-group">
    {!! Form::label('periodo_apuracao', 'Periodo Apuração', ['class' => 'control-label']) !!}
    {!! Form::text('periodo_apuracao',null, ['class' => 'form-control','style' => 'width:80px']) !!}
</div>

<div class="form-group">
    <a href="#" id="ButtonEmpresas" class="btn btn-default" onclick="Empresas()">Atividade Empresas Selecionadas</a>
    <a href="#" id="ButtonEstabelecimento" class="btn btn-default" onclick="Estabelecimentos()">Atividade Estabelecimentos Selecionados</a>
</div>
<br/>
{!! Form::close() !!}

{!! Form::open([
    'route' => 'cronogramaatividades.storeEstabelecimento',
    'id' => 'storeEstabelecimento'
]) !!}

    {!!  Form::hidden('multiple_select_estabelecimentos_frm[]', '', array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}

    {!! Form::hidden('periodo_apuracao_estab',null, ['class' => 'form-control','style' => 'width:80px']) !!}
    {!! Form::hidden('select_tributo_estab',null, ['class' => 'form-control','style' => 'width:80px']) !!}

{!! Form::close() !!}



{!! Form::open([
    'route' => 'cronogramaatividades.storeEmpresa',
    'id' => 'storeEmpresa'
]) !!}

    {!!  Form::hidden('multiple_select_empresas_frm[]', '', array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}

    {!! Form::hidden('periodo_apuracao_emps',null, ['class' => 'form-control','style' => 'width:80px']) !!}

{!! Form::close() !!}



<script>
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});

function Estabelecimentos(){
    var periodo = $('input[name="periodo_apuracao"]').val();
    var opcoes = [];
    var estabelecimentos = $('#multiple_estab :selected');  
    var tributo = $('#select_tributo :selected').val();  
    
    estabelecimentos.each(function(i, selecionado){
      if($.inArray(selecionado,opcoes) == -1){
        opcoes.push(selecionado.value);
      }else{
        opcoes.splice(i,1);
      }
    });
    $('input[name="multiple_select_estabelecimentos_frm[]"]').val(opcoes);
    $('input[name="periodo_apuracao_estab"]').val(periodo);
    $('input[name="select_tributo_estab"]').val(tributo);
    if ($('input[name="multiple_select_estabelecimentos_frm[]"]').val() != '') {
        $('#storeEstabelecimento').submit();
    }
}

function Empresas(){
    var periodo = $('input[name="periodo_apuracao"]').val();
    var opcoes = [];
    var empresas = $('#multiple_emps :selected');  
    
    empresas.each(function(i, selecionado){
      if($.inArray(selecionado,opcoes) == -1){
        opcoes.push(selecionado.value);
      }else{
        opcoes.splice(i,1);
      }
    });
    $('input[name="multiple_select_empresas_frm[]"]').val(opcoes);
    $('input[name="periodo_apuracao_emps"]').val(periodo);
    if ($('input[name="multiple_select_empresas_frm[]"]').val() != '') {
        $('#storeEmpresa').submit();
    }
}


</script>

@stop



