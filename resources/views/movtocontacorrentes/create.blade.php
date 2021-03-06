@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Adicionar nova Conta Corrente</h1>
<hr>
{!! Form::open([
    'route' => 'movtocontacorrentes.store'
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
        {!! Form::text('periodo_apuracao', $periodo_apuracao, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('area', 'Area:', ['class' => 'control-label']) !!}
        {!! Form::text('area', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
        {!! Form::label('estabelecimento', 'Estabelecimento:', ['class' => 'control-label']) !!}
        {!! Form::text('estabelecimento', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:40%">
        {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
        {!! Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:40%">
        {!! Form::label('ie', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
        {!! Form::text('ie', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
        </div>
    </div>


    <div class="form-group">
        <div style="width:40%">
        {!! Form::label('cidade', 'Cidade:', ['class' => 'control-label']) !!}
        {!! Form::text('cidade', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:40%">
        {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
        {!! Form::text('uf', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('valor_guia', 'Valor Guia R$:', ['class' => 'control-label']) !!}
        {!! Form::text('vlr_guia', null, ['class' => 'form-control', 'id'=> 'vlr_guia']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('valor_gia', 'Valor Gia R$:', ['class' => 'control-label']) !!}
        {!! Form::text('vlr_gia', null, ['class' => 'form-control', 'id'=> 'vlr_gia']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('valor_sped', 'Valor Sped R$:', ['class' => 'control-label']) !!}
        {!! Form::text('vlr_sped', null, ['class' => 'form-control', 'id'=> 'vlr_sped']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('dipam', 'DIPAM:', ['class' => 'control-label']) !!}
        {!! Form::checkbox('dipam', 'S', false) !!}
       </div>
    </div>

    <div class="form-group" id="vlr_dipam_div" style="display: none">
        <div style="width:30%">
        {!! Form::label('vlr_dipam', 'Valor Dipam R$:', ['class' => 'control-label']) !!}
        {!! Form::text('vlr_dipam', null, ['class' => 'form-control', 'id'=> 'vlr_dipam']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:30%">
            {!! Form::label('status', 'Status:', ['class' => 'control-label']) !!}
            {!! Form::select('status_id', $status, null, array('class' => 'form-control')) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
            {!! Form::textarea('observacao', '', array('class' => 'form-control', 'id'=>'observacao')) !!}
        </div>
    </div>
    {!! Form::hidden('estabelecimento_id', null, ['class' => 'form-control', 'id'=> 'estabelecimento_id']) !!}
    {!! Form::submit('Cadastrar', ['class' => 'btn btn-default']) !!}
</div>

<div class="col-md-4">
    <div class="detailBox">
        <div class="actionBox">
            <div>
                <div style="width: 50%">
                {!! Form::label('Data_inicio', 'Data Início:', ['class' => 'control-label']) !!}
                {!! Form::date('Data_inicio', null, ['class' => 'form-control']) !!}
                </div>
                <div style="width: 50%">
                {!! Form::label('DataPrazo', 'Data Prazo:', ['class' => 'control-label']) !!}
                {!! Form::date('DataPrazo', null, ['class' => 'form-control']) !!}
                </div>
                <div style="width: 70%">
                {!! Form::label('Responsavel', 'Responsável:', ['class' => 'control-label']) !!}
                {!! Form::select('Responsavel', $Responsaveis, null, array('class' => 'form-control')) !!}
                </div>
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");

    $("#vlr_guia, #vlr_sped, #vlr_gia, #vlr_dipam").maskMoney({symbol:'R$ ', allowZero:true,
            showSymbol:false, thousands:'.', decimal:',', symbolStay: false, defaultZero: true});

    $( "#area" ).change(function() { 
        $.ajax(
        {
            type: "GET",
            url: '{{ url('estabelecimento') }}/search_area',
            cache: false,
            async: false,
            dataType: "json",
            data:
            {
                'codigo_area':$(this).val()
            },
            success: function(d)
            {
                if (!d.success) {

                    alert('Código de Área não existe');
                    $("#estabelecimento").val('');
                    $("#estabelecimento_id").val('');
                    $("#cnpj").val('');
                    $("#ie").val('');
                    $("#cidade").val('');
                    $("#uf").val('');
                    $("#area").val('');
                    $("#area").focus();
                    return false;
                }       

                $("#estabelecimento").val(d.data.estabelecimento.razao_social);
                $("#estabelecimento_id").val(d.data.estabelecimento.id);
                $("#cnpj").val(printMask(d.data.estabelecimento.cnpj));
                $("#ie").val(d.data.estabelecimento.insc_estadual);
                $("#cidade").val(d.data.municipio.nome);
                $("#uf").val(d.data.municipio.uf);
            }
        });
    });    

    $( "#dipam" ).change(function() {
        if ($(this).is(':checked')) {
            $("#vlr_dipam_div").show();
        } else {
            $("#vlr_dipam_div").hide();
            $("#vlr_dipam").val('0');
        }
    });      
});

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
</script>

@stop



