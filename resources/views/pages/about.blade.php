@extends('layouts.master')

@section('content')

<div class="about">
@if (!Auth::guest())
    <div class="content-top">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title">@foreach($users as $user)
                    @if ($user->isOnline())
                        <img src="{{ URL::to('/') }}/assets/img/{{$user->roles[0]->name}}-icon.png" class="top-image" title="{{$user->name}}" />
                        <!--i title="{{$user->name}} online" class="fa fa-user"></i-->
                    @endif
                @endforeach Online <span>/ Entregas</span></h1>
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

@stop
