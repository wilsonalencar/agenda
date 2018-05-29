@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<?php if (@!empty($status)) { ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
<?php } ?>

<?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
      <?php echo $message; ?>
    </div>
<?php } ?>

<h1>Atividade do analista</h1>
<hr>
{!! Form::open([
    'route' => 'atividadesanalista.store'
]) !!}

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Emp_id', 'Empresa', ['class' => 'control-label'] )  !!}
    {!!  Form::select('Emp_id', $empresas, array(), ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Tributo_id', 'Atividade Tributo', ['class' => 'control-label'] )  !!}
    {!!  Form::select('Tributo_id[]', $tributos, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Id_usuario_analista', 'Analista', ['class' => 'control-label'] )  !!}
    {!!  Form::select('Id_usuario_analista', $usuarios, array(), ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Todos os estabelecimentos:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('Regra_geral', 'S', 'S', ['id' => 'regra_geral_SIM']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('Regra_geral', 'N', '', ['id' => 'regra_geral_NAO']) !!}
    </div>
</div>
{!! Form::submit('Adicionar', ['class' => 'btn btn-default']) !!}
{!! Form::close() !!}
<hr/>


<script type="text/javascript">
  $('select').select2();
</script>
@stop