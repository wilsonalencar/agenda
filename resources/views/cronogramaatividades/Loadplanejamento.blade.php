@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Gerar Planejamento</h1>
<hr>
{!! Form::open([
    'route' => 'cronogramaatividades.planejamento'
]) !!}

<div class="col-md-8">
    
    <div class="form-group">
        <div>
        {!! Form::label('Empresas', 'Empresas :', ['class' => 'control-label']) !!}
        {!!  Form::select('empresas[]', $empresas, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
        </div>
    </div>
    
    <div class="form-group">
        <div style="width:50%">
        {!! Form::label('tributos', 'Tributos :', ['class' => 'control-label']) !!}
        {!!  Form::select('tributos[]', $tributos, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:20%">
        {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
        {!! Form::text('periodo_apuracao', '', ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:10%">
        {!! Form::label('uf', 'UF :', ['class' => 'control-label']) !!}
        {!!  Form::select('uf', $uf, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
        </div>
    </div>
    
    {!! Form::submit('Gerar', ['class' => 'btn btn-default']) !!}
</div>



{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");      
});
$('select').select2();

</script>

@stop



