@extends('...layouts.master')

@section('content')
<form action="home" method="get">
<h1>Selecionar Empresa</h1>
<p class="lead"> 
   <div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_tributos[]', 'Empresas', ['class' => 'control-label'] )  !!} <br>
    {!!  Form::select('empresa_selecionada', $empresas, array(), []) !!}
    </div>
    {!! Form::submit('Selecionar Empresa', ['class' => 'btn btn-default']) !!}
</div>
</p>
</form>
<hr>
@stop
