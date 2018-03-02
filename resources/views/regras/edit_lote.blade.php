@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<?php
foreach ($dados as $key => $value) {
}
?>

<h1>Regras de envio por lote</h1>
<hr>
{!! Form::open([
    'route' => 'regraslotes.store'
]) !!}

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('select_tributos', 'Empresas', ['class' => 'control-label'] )  !!}
    {!! Form::select('empresasview', $empresas, $value['id_empresa'], ['class' => 'form-control s2', 'disabled' => 'true']) !!}
    {!! Form::hidden('select_empresas', $value['id_empresa'], ['class' => 'form-control']) !!}    
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('select_tributos', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
    {!! Form::select('tributosview', $tributos, $value['id_tributo'], ['class' => 'form-control s2', 'disabled' => 'true']) !!}
    {!! Form::hidden('select_tributos', $value['id_tributo'], ['class' => 'form-control']) !!}    

    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Regra geral:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('label_regra', true, ( $value['regra_geral'] == "S" ? true : false ), ['id' => 'regra_geral_SIM', 'onClick' => 'hideDiv()']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('label_regra', false, ( $value['regra_geral'] == "N" ? true : false ), ['id' => 'regra_geral_NAO', 'onClick' => 'showDiv()']) !!}

    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_1', 'E-Mail obrigatório:', ['class' => 'control-label']) !!}
    {!! Form::text('email_1', $value['email_1'], ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_2', 'E-Mail opcional:', ['class' => 'control-label']) !!}
    {!! Form::text('email_2', $value['email_2'], ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_3', 'E-Mail opcional:', ['class' => 'control-label']) !!}
    {!! Form::text('email_3', $value['email_3'], ['class' => 'form-control']) !!}
    </div>
</div>

{!! Form::hidden('id', $value['id'], ['class' => 'form-control']) !!}
{!! Form::hidden('add_cnpj', 0, ['class' => 'form-control']) !!}    
{!! Form::submit('Salvar', ['class' => 'btn btn-default']) !!}
{!! Form::close() !!}
<hr/>

<div id="hidden_div" style="display:none;">
    {!! Form::open([
        'route' => 'regraslotes.store'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
        {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
        {!! Form::text('cnpj', '', ['class' => 'form-control']) !!}
        {!! Form::submit('Adicionar', ['class' => 'btn btn-default']) !!}
        </div>
        
    </div>
    <table style="width: 50%" class="table table-bordered display">   
        <thead>
            <tr>
                <th>CNPJ</th>
                <th>Área</th>
                <th width="10px"></th>
            </tr>
        </thead>
        <tbody>
        @if (!empty($dadosfiliais))
            @foreach ($dadosfiliais as $chave => $date)        
            <tr>
               <td><?php echo mask($date['CNPJ'],'##.###.###/####-##'); ?></td>
               <td><?php echo $date['codigo']; ?></td>
               <td><a id="excluiRegFilial" style="margin-left:10px" class="btn btn-default btn-sm" onclick="confirma()"><i class="fa fa-trash"></i></a></td>
            </tr> 
            @endforeach
        @endif
            
        </tbody>
    </table>            
    <br />  
    {!! Form::hidden('id', $value['id'], ['class' => 'form-control']) !!}
    {!! Form::hidden('id_empresa', $value['id_empresa'], ['class' => 'form-control']) !!}
    {!! Form::hidden('add_cnpj', 1, ['class' => 'form-control']) !!}    
    {!! Form::close() !!}    
</div>


<script type="text/javascript">
    $('select').select2();
    if (document.getElementById('regra_geral_NAO').checked) {
       document.getElementById('hidden_div').style.display = "block";
    }

function confirma() {
    if (confirm("Você tem certeza que quer deletar o registro?") == true) {
        <?php if (!empty($date['id'])) { ?>
        window.location="{{ route('regraslotes.excluirFilial', $date['id']) }}";
        <?php } ?>
    }
}

jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
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