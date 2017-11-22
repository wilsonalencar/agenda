@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-md-9">
        <p class="lead">Devido ao alto volume de estabelecimentos, localizados em áreas diferentes, existe uma
        complexidade do controle de todas as entregas tributárias a ser efetuadas no ano fiscal. Por este motivo,
        identificou-se a necessidade de construir uma ferramenta que ajude o time no gerenciamento das datas de
        entrega para torná-lo mais eficiente e, ao mesmo tempo, minimizar o risco de erros ou atrasos.</p>
        <img src="{{ URL::to('/') }}/assets/img/agenda-fiscal.png" /><br/>
        <hr>
    </div>
    <div class="col-md-3">
        @if (!Auth::guest())
        <div class="row">
            <h3>ONLINE</h3>
            @foreach($users as $user)
                @if ($user->isOnline())
                    <img src="{{ URL::to('/') }}/assets/img/{{$user->roles[0]->name}}-icon.png" title="{{$user->name}}" />
                    <!--i title="{{$user->name}} online" class="fa fa-user"></i-->
                @endif
            @endforeach
        </div>
        <div class="row">
            <h3>ENTREGAS</h3>
            <table>
            <thead>
                <th>Usuário</th>
                <th colspan="2">Entregas/Prazo</th>
            </thead>
            @foreach($standing as $user)
            <tr>
               <td style="font-size:6;">{{$user->name}}</td>
               <td><i style="padding: 0px 5px 0px 5px" class="fa fa-info" title="{{$user->entrega_em_prazo}} de {{$user->entregas_totais}}"></i></td>
               <td style="font-size:6; color:#333; text-align:right">{{$user->perc}} %</td>
            </tr>
            @endforeach
            </table>
        </div>
        @endif
    </div>
</div>

@stop
