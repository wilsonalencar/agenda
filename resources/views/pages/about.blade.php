@extends('layouts.master')

@section('content')
<div class="row" align="center">
    
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
        <div class="row" align="center">
            <h3>ENTREGAS</h3>
            <table>
            <thead>
                <th>Usuário</th>
                <th colspan="2">Entregas/Prazo</th>
            </thead>
            @foreach($standing as $user)
            <tr>
               <td style="font-size:6;">{{$user->name}}</td>
               <td><i class="fa fa-info" title="{{$user->entrega_em_prazo}} de {{$user->entregas_totais}}"></i></td>
               <td style="font-size:6; color:#333;">{{$user->perc}} %</td>
            </tr>
            @endforeach
            </table>
        </div>
        @endif
    </div>
</div>

@stop
