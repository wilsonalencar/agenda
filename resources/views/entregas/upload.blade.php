@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

<div style="float:right" class="detailBox">
          <div class="titleBox">
            <label>Comentários sobre a atividade</label>
          </div>
          <div class="commentBox">
              <p class="taskDescription">Nesta caixa são armazenados os comentários efetuados pelos usuários sobre esta atividade.</p>
          </div>
          <div class="actionBox">
              <ul class="commentList">
                  @foreach($atividade->comentarios as $el)
                  <li>
                      <div>
                        <p class="commenterName">{{$el->user->name}}</p><p class="commentText">{{ $el->obs }}</p> <span class="date sub-text"> ({{ $el->created_at }})</span>
                      </div>
                  </li>
                  @endforeach
              </ul>

          </div>
      </div>

<div class="about-section">
   <div class="text-content">
     <div class="span7 offset1">

        <h2>Entrega recibo atividade</h2>
        <h3>Tributo: {!! $atividade->regra->tributo->nome !!} - Descrição: {!! $atividade->regra->nome_especifico !!} {!! $atividade->descricao !!}</h3>
        <h3>Periodo Apuração: {!! $atividade->periodo_apuracao !!}</h3>
        <h3>Estabelecimento: {{ mask($atividade->estemp->cnpj,'##.###.###/####-##') }}</h3><br/>
        <small>Data limite para entrega: {{ Date_Converter($atividade->limite) }}</small><br/>
        <small>Data atual: {{ Date_Converter(date('Y-m-d H:m:s')) }}</small><br/>
        <br/>
        <span>O documento será inserido no workflow de processo para aprovação do responsável.</span>
        <br/>
        <hr/>
        @if(Session::has('success'))
          <div class="alert-box success">
          <h2>{!! Session::get('success') !!}</h2>
          </div>
        @endif
        {!! Form::open(array('url'=>'upload/sendUpload','method'=>'POST', 'files'=>true)) !!}
         <div class="control-group">
          <div class="controls">
                {!! Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']) !!}
                {!! Form::file('image', array('class'=>'btn btn-default ')) !!}

                @if(Session::has('error'))
                    <p style="color:red; font-weight: bold" class="errors">{!! Session::get('error') !!}</p>
                @endif
          </div>
        </div>
        <div id="success"> </div>
        <br/>
        {!! Form::submit('Envio com documentação', array('class'=>'btn btn-default ')) !!}
        {!! Form::close() !!}
      </div>
      <hr>
      <div>
        {!! Form::open([
            'route' => 'atividades.storeComentario'
        ]) !!}

            {!! Form::label('obs', 'Comentario (max.120 caracteres)', ['class' => 'control-label']) !!}
            {!! Form::textarea('obs', null, ['style'=> 'width:500px; height:50px','class' => 'form-control']) !!}
            {!! Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']) !!}
            {!! Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control']) !!}
            <br/>
            {!! Form::submit('Adiciona comentario', ['name'=>'com','class' => 'btn btn-default']) !!}
            {!! Form::submit('Adiciona comentario e envia sem documentação', array('name'=>'esd','class'=>'btn btn-default ')) !!}
            {!! Form::close() !!}

        {!! Form::close() !!}

        <br/>
      </div>
   </div>
</div>
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
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}

?>

@stop

