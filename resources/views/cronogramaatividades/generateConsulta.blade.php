@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'ConsultaCronograma'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-2">
                {!! Form::label('semana_busca', 'Data Início', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">
                {!! Form::label('semana_busca', 'Data Fim', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <hr/><hr/>
        <div class="row">
            <div class="col-md-8">
                {!! Form::label('empresas', 'Empresas', ['class' => 'control-label'] )  !!}         
                {!!  Form::select('empresas_selected[]', $empresas, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-8">
                {!! Form::label('empresas', 'Analistas', ['class' => 'control-label'] )  !!}         
                {!!  Form::select('analista_selected[]', $analistas, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>  
        <hr /><hr/>
        <div class="row">
            <div class="col-md-8">
                {!! Form::label('empresas', 'Filiais', ['class' => 'control-label'] )  !!}         
                {!!  Form::select('estabelecimento_selected[]', $estabelecimentos, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-8">
                {!! Form::label('empresas', 'Tributos', ['class' => 'control-label'] )  !!}         
                {!!  Form::select('tributos_selected[]', $tributos, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-8">
                {!! Form::label('empresas', 'Status', ['class' => 'control-label'] )  !!}         
                {!!  Form::select('status_selected[]', $status, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-2">
                {!! Form::submit('Consultar', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
<script type="text/javascript">
    
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>