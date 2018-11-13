@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Guias Cadastradas</h1>
            <p class="lead"><button href="#" id="filtros" class="btn btn-default" data-toggle="modal" data-target="#myModalFill">Filtros</button> </p>
        </div>
    </div>
</div>

<?php if ($status == 'success' && !empty($msg)) { ?>
    <div class="alert alert-success">
        <?php echo $msg; ?>
    </div>
<?php } ?>

<?php if ($status == 'error' && !empty($msg)) { ?>
    <div class="alert alert-danger">
      <?php echo $msg; ?>
    </div>
<?php } ?>


   <table class="table table-bordered" id="myTableAprovacao" style="font-size: 13px">   
        <thead>
            <tr>
                <th>CNPJ </th>
                <th>REFERENCIA </th>
                <th>DATA_VENCTO </th>
                <th>CÓDIGO ESTAB </th>
                <th>UF </th>
                <th>VLR_TOTAL </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @if (!empty($registros))
            @foreach ($registros as $key => $registro)  
            <tr>
            <?php
            $valorData = $registro->DATA_VENCTO;
            $data_vencimento = str_replace('/', '-', $valorData);
            $registro->DATA_VENCTO = date('d/m/Y', strtotime($data_vencimento));
            ?>
                <td><?php if (strlen($registro->CNPJ) == 14) {
                    echo printMaskCnpj($registro->CNPJ);
                } else { echo $registro->CNPJ; } ?> </td>
                <td><?php echo $registro->REFERENCIA; ?> </td>
                <td><?php echo $registro->DATA_VENCTO; ?> </td>
                <td><?php echo $registro->CODIGO; ?> </td>
                <td><?php echo $registro->UF; ?> </td>
                <td>R$ <?php echo $registro->VLR_TOTAL; ?> </td>
                <td align="center"><a href="{{ route('guiaicms.editar', $registro->ID) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i><a href="{{ route('guiaicms.excluir', $registro->ID) }}" class="btn btn-default btn-sm"><i class="fa fa-trash"></i></a>
               </td>
            </tr> 
            @endforeach
        @endif 
        </tbody>
    </table>                                            

<div id="myModalFill" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Filtros de Data</h4>
      </div>
      <div class="modal-body">
        {!! Form::open([
            'route' => 'guiaicms.listar',
            'method' => 'GET'
        ]) !!}

        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('data_inicio', 'Data de busca Inicial', ['class' => 'control-label'] )  !!}
                {!!  Form::date('data_inicio', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>
        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('data_termino', 'Data de Busca Final', ['class' => 'control-label'] )  !!}
                {!!  Form::date('data_termino', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>

        {!! Form::submit('Filtrar', ['class' => 'btn btn-success']) !!}
        {!! Form::close() !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Voltar</button>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Pesquisar por GUIA",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "lfrtip",
        processing: true,
        stateSave: true,
        lengthMenu: [[25, 50, 75, -1], [25, 50, 75, "100"]]
    });        
});

</script>

<?php
function printMaskCnpj($data) {
    return substr($data, 0,2).'.'.substr($data, 2,3).'.'.substr($data,5,3).'/'.substr($data, 8,4).'-'.substr($data, 12,2);
    return substr($data, 0,2).'.'.substr($data, 2,3).'.'.substr($data,5,8).'/'.substr($data, 8,12).'-'.substr($data, 12,14);
}
?>
@stop