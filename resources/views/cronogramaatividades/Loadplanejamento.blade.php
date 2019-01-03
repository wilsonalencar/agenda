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
        <div style="width:30%">
        {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
        {!! Form::text('periodo_apuracao', '', ['class' => 'form-control']) !!}
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

</script>

@stop



