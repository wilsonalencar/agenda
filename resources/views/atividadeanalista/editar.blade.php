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
        <?php echo $status; ?>
    </div>
<?php } ?>

<?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
      <?php echo $error; ?>
    </div>
<?php } ?>

<h1>Atividade do analista</h1>
<hr>
{!! Form::open([
    'route' => 'atividadesanalista.edit'
]) !!}

<input type="hidden" name="old_empid" value="{{ $selected_empresa }}">
<input type="hidden" name="old_tributoid" value="{{ $selected_tributo }}">
<input type="hidden" name="old_uf" value="{{ $selected_uf }}">
<input type="hidden" name="old_regrageral" value="{{ $selected_regra_geral }}">

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Emp_id', 'Empresas', ['class' => 'control-label'] )  !!}
    {!! Form::select('Emp_id_e', $empresas, $selected_empresa, ['class' => 'form-control s2', 'disabled' => 'disabled']) !!}
    <input type="hidden" name="Emp_id" value="{{ $selected_empresa }}">
    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Tributo_id', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
    {!! Form::select('Tributo_id_e[]', $tributos, $selected_tributo, ['class' => 'form-control s2_multi', 'multiple' => 'multiple', 'disabled' => 'disabled']) !!}
    <input type="hidden" name="Tributo_id[]" value="{{ $selected_tributo }}">
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Id_usuario_analista', 'Analista', ['class' => 'control-label'] )  !!}
    {!! Form::select('Id_usuario_analista[]', $usuarios, $selected_users, ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:5%">
    {!! Form::label('UF', 'UF', ['class' => 'control-label'] )  !!}
    {!!  Form::text('uf', $selected_uf, ['class' => 'form-control', 'maxlength' => 2, 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Regra geral:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('Regra_geral', 'S', ( $selected_regra_geral == "S" ? true : false ), ['id' => 'regra_geral_SIM']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('Regra_geral', 'N', ( $selected_regra_geral == "N" ? true : false ), ['id' => 'regra_geral_NAO']) !!}
    </div>
</div>

{!! Form::submit('Salvar', ['class' => 'btn btn-default']) !!}
    <a href="{{route('atividadesanalista.index')}}" class="btn btn-default">Voltar</a>
{!! Form::close() !!}
<hr/>
    
<script type="text/javascript">

$('select').select2();

</script>
@stop