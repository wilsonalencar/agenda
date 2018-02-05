@extends('layouts.master')

@section('content')

{!! Form::open([
    'route' => 'about'
]) !!}


<div class="about">
@if (!Auth::guest())
   <div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Entregas</h1>
        </div>
        <div class="col-md-8">
            <div class="refresh-option">
                {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
                {!! Form::button('<i class="fa fa-refresh"></i>', array('id' => 'atualiza_btn', 'class'=>'refresh-icon', 'type'=>'submit')) !!}
                {!! Form::close() !!}
            </div>
            <div class="period">
                
                <div class="input-group spinner">
                    <input type="text" class="form-control" value="{{substr($periodo,0,2)}}/{{substr($periodo,-4,4)}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>{!! Form::label('codigo', 'Periodo apuração:', ['class' => 'control-label']) !!}</span>
            </div>
        </div>
    </div>
</div>
@endif

    <div class="row">
        
        <div class="col-md-12">
                <div class="table-responsive table-about">            
                    <table class="table table-striped table-bordered table-list">
                    <thead>
                        <th>Usuário</th>
                        <th>Entregas / Prazo</th>
                    </thead>
                    @foreach($standing as $user)
                    <tr>
                    <td>{{$user->name}}</td>
                    <td><i class="fa fa-info" title="{{$user->entrega_em_prazo}} de {{$user->entregas_totais}}"></i> {{$user->perc}} %</td>
                    </tr>
                    @endforeach
                    </table>
                </div>
            </div>
            
        </div>
    </div>

</div>
<script>
jQuery(document).ready(function($){
    $('.spinner .btn:first-of-type').on('click', function() { //UP
            var value = $('.spinner input').val();

            var mes = parseInt(value.substr(0,2));
            var year = parseInt(value.substr(3,4));
            mes += 1;
            if (mes>12) {
                mes = 1;
                year += 1;
            } else if (mes<10) {
                mes = '0'+mes;
            }
            year = ''+year;
            $('.spinner input').val(mes+'/'+year);

            $('input[name="periodo_apuracao"]').val(mes+year);
            $( "#atualiza_btn" ).click();

      });

     $('.spinner .btn:last-of-type').on('click', function() {  //DOWN
            var value = $('.spinner input').val();

            var mes = parseInt(value.substr(0,2));
            var year = parseInt(value.substr(3,4));
            mes -= 1;
            if (mes<1) {
                mes = 12;
                year -= 1;
            } else if (mes<10) {
                mes = '0'+mes;
            }
            year = ''+year;
            $('.spinner input').val(mes+'/'+year);

            $('input[name="periodo_apuracao"]').val(mes+year);
            $( "#atualiza_btn" ).click();
     });
});
</script>
@stop
