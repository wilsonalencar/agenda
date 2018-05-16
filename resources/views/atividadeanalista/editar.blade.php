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

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Emp_id', 'Empresas', ['class' => 'control-label'] )  !!}
    {!! Form::select('Emp_id', $empresas, $dados['Emp_id'], ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Tributo_id', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
    {!! Form::select('Tributo_id', $tributos, $dados['Tributo_id'], ['class' => 'form-control s2']) !!}

    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('Id_usuario_analista', 'Analista', ['class' => 'control-label'] )  !!}
    {!!  Form::select('Id_usuario_analista', $usuarios, $dados['Id_usuario_analista'], ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Regra geral:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('Regra_geral', 'S', ( $dados['Regra_geral'] == "S" ? true : false ), ['id' => 'regra_geral_SIM', 'onClick' => 'hideDiv()']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('Regra_geral', 'N', ( $dados['Regra_geral'] == "N" ? true : false ), ['id' => 'regra_geral_NAO', 'onClick' => 'showDiv()']) !!}
    </div>
</div>

{!! Form::hidden('id', $dados['id'], ['class' => 'form-control']) !!}
{!! Form::submit('Salvar', ['class' => 'btn btn-default']) !!}
    <a href="{{route('atividadesanalista.index')}}" class="btn btn-default">Voltar</a>
{!! Form::close() !!}
<hr/>

<div id="hidden_div" style="display:none;">
    {!! Form::open([
        'route' => 'atividadesanalistafilial.store'
    ]) !!}
    <div class="form-group">
        <div style="width:50%">
        {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
        {!! Form::text('cnpj_exibe', NULL, ['class' => 'form-control']) !!}
        {!! Form::hidden('cnpj', NULL, ['class' => 'form-control']) !!}
        {!! Form::hidden('Id_atividadeanalista', $dados['id'], ['class' => 'form-control']) !!}
            <div class="pull-right">
            <br>
                {!! Form::submit('Adicionar', ['class' => 'btn btn-default', 'onclick' => 'myfunction()']) !!}
            </div>
        </div>
        
    </div>
    <br><br><br>

    <table style="width: 50%" class="table table-bordered display">   
        <thead>
            <tr>
                <th>CNPJ</th>
                <th>Área</th>
                <th width="10px"></th>
            </tr>
        </thead>
        <tbody>
        @if (!empty($cnpjs))
            @foreach ($cnpjs as $chave => $date)  
            <tr>
               <td><?php echo mask($date['cnpj'],'##.###.###/####-##'); ?></td>
               <td><?php echo $date['codigo']; ?></td>
               <td><a id="excluiRegFilial" style="margin-left:10px" class="btn btn-default btn-sm" onclick="confirma()"><i class="fa fa-trash"></i></a></td>
            </tr> 
            @endforeach
        @endif
            
        </tbody>
    </table>            
    <br />  
    {!! Form::close() !!}    
</div>


<script type="text/javascript">
    $('select').select2();
    if (document.getElementById('regra_geral_NAO').checked) {
       document.getElementById('hidden_div').style.display = "block";
    }
function myfunction() {
    cnpj = $('input[name="cnpj_exibe"]').val();    
    $('input[name="cnpj"]').val(cnpj);
    $('input[name="cnpj_exibe"]').val('');
}

function confirma() {
    if (confirm("Você tem certeza que quer deletar o registro?") == true) {
        <?php if (!empty($date['id'])) { ?>
        window.location="{{ route('atividadesanalistafilial.excluirFilial', $date['id']) }}";
        <?php } ?>
    }
}

jQuery(function($){
    $('input[name="cnpj_exibe"]').mask("99.999.999/9999-99");
});

function showDiv(){
   document.getElementById('hidden_div').style.display = "block";
}

function hideDiv(){
   document.getElementById('hidden_div').style.display = "none";
}

</script>
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
?>
@stop