@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')
<style type="text/css">
    #container {
        width: 100%;
        text-align: center;
    }

    .box {
        float: left;
        width: 100px; height: 70px;
        margin: 10px 20px;
    }
</style>
<h1>Editar Documento</h1>


@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<hr>
{!! Form::open([
    'route' => ['documentacao.editar', $request->id]
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:100%, height:80%">
        {!! Form::label('descricao', 'Descrição:', ['class' => 'control-label']) !!}
        {!! Form::text('descricao', $request->descricao, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group">

        <div style="width:50%,">
        {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
        {!! Form::textarea('observacao', $request->observacao, ['class' => 'form-control']) !!}
        </div>
    </div>
        {!! Form::hidden('versao', $request->versao, ['class' => 'form-control']) !!}
        {!! Form::submit('Atualizar', ['class' => 'btn btn-default']) !!}
        <div style="width: 100%" class="container" align="right">
            {!! Form::label('versao', 'Versão: '.$request->versao.'.0', ['class' => 'control-label']) !!}
        </div>
</div>

{!! Form::close() !!}
<hr/>

@stop