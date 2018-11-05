@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'arquivos.downloads'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title"> </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
            {!! Form::label('estabelecimentos_selected[]', 'Selecionar estabelecimentos', ['class' => 'control-label'] )  !!}
                {!!  Form::select('estabelecimentos_selected[]', $estabelecimentos, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-md-5">
            {!! Form::label('uf_selected[]', 'Selecionar UFS', ['class' => 'control-label'] )  !!}
                {!!  Form::select('ufs[]', $ufs, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <BR />

        <div class="row">
            <div class="col-md-5">
            {!! Form::label('tributo_id', 'Selecionar Tributo', ['class' => 'control-label'] )  !!}
                {!!  Form::select('tributo_id', $tributos, '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <BR />
        <div class="row">
            <div class="col-md-2">
                {!! Form::label('data_entrega_inicio', 'Data Início Entrega', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_entrega_inicio', '', ['class' => 'form-control']) !!}
            </div>

            <div class="col-md-2">
                {!! Form::label('data_entrega_fim', 'Data Fim Entrega', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_entrega_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />

        <div class="row">
            <div class="col-md-2">
                {!! Form::label('data_aprovacao_inicio', 'Data Início Aprovação', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_aprovacao_inicio', '', ['class' => 'form-control']) !!}
            </div>

            <div class="col-md-2">
                {!! Form::label('data_aprovacao_fim', 'Data Fim Aprovação', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_aprovacao_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />

        <div class="row">
            <div class="col-md-2">    
                {!! Form::label('periodo_apuracao_inicio', 'Periodo de apuração', ['class' => 'control-label'] )  !!}     
                {!! Form::text('periodo_apuracao_inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">    
                {!! Form::label('periodo_apuracao_fim', 'Periodo de apuração', ['class' => 'control-label'] )  !!}     
                {!! Form::text('periodo_apuracao_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />


        <div class="row">
            <div class="col-md-2">
                {!! Form::submit('Download', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
<script type="text/javascript">
    
jQuery(function($){
    $('input[name="periodo_apuracao_inicio"]').mask("99/9999");
    $('input[name="periodo_apuracao_fim"]').mask("99/9999");
    $('select').select2();
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>