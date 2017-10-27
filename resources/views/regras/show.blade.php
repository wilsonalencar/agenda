@extends('layouts.master')

@section('content')

<h1>REGRA</h1>
<p class="lead">TRIBUTO: {{ $tributo->nome }}</p>
<p class="lead">NOME ESPECIFICO: {{ $regra->nome_especifico }}</p>
<p class="lead">REFERÊNCIA: {{ $regra->ref }}</p>
<p class="lead">ADIANTAMENTO ENTREGA NO FIM SEMANA: {{ $regra->afds?'SIM':'NÃO' }}</p>
<hr>
<p>PROXIMA(S) ENTREGA(S) PREVISTA(S):</p>
@foreach($entregas as $entrega)
<b>{{ substr($entrega['data'],0,10) }}</b>{{' ('.$entrega['desc'].')'}}
<br/>
@endforeach
<br/>
@if($empresas)
    <div style="margin-left:20px; padding-bottom: 20px" class="row">
        EMPRESAS:
    </div>
    @foreach($empresas as $empresa)
    <div class="row">
        <div class="col-md-2">
            <a href="{{ route('empresas.show', $empresa->id) }}" style="margin-left:10px" class="btn btn-default btn-sm">{{mask($empresa->cnpj,'##.###.###/####-##')}}</a>
        </div>
        <div class="col-md-2">
            {{ ' CODIGO: '.$empresa->codigo }}
        </div>
         <div class="col-md-2">
            {{ $empresa->nome.' ('.$empresa->uf.') ' }}
        </div>
    </div>
    @endforeach
@endif
<br/><br/>
@if($estabs)
    <div style="padding-bottom: 20px" class="row">
        ESTABELECIMENTOS:
    </div>
    @foreach($estabs as $estab)
    <div class="row">
        <div class="col-md-2">
            <a href="{{ route('estabelecimentos.show', $estab->id) }}" style="margin-left:10px" class="btn btn-default btn-sm">{{mask($estab->cnpj,'##.###.###/####-##')}}</a>
        </div>
        <div class="col-md-2">
            {{ ' CODIGO: '.$estab->codigo }}
        </div>
        <div class="col-md-2">
            {{ $estab->nome.' ('.$estab->uf.') ' }}
        </div>
        <div class="col-md-2">
            @if(in_array($estab->id,$blacklist))
                <a href="{{ route('regras.setBlacklist', array($regra->id,$estab->id,0)) }}" style="color:red; padding-left:50px">INATIVO</a>
            @else
                <a href="{{ route('regras.setBlacklist', array($regra->id,$estab->id,1)) }}" style="color:green; padding-left:50px">ATIVO</a>
            @endif
        </div>
    </div>
    @endforeach
@endif
<hr>
<div class="row">
    <div class="col-md-6">
        <a href="{{ route('regras.index') }}" class="btn btn-default">Voltar para todas as regras</a>
        <a href="{{ route('calendario') }}" class="btn btn-default">Voltar para Calendario</a>
    </div>
    <div class="col-md-6 text-right">
        {!! Form::open([
            'method' => 'DELETE',
            'route' => ['regras.destroy', $regra->id]
        ]) !!}
            {!! Form::submit('Cancelar esta regra?', ['class' => 'btn btn-default']) !!}
        {!! Form::close() !!}
    </div>
</div>
<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });

</script>
@stop

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