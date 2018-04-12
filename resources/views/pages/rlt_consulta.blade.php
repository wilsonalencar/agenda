@extends('...layouts.master')

@section('content')

@if (Session::has('message'))
   <div class="alert alert-info">{{ Session::get('message') }}</div>
@endif

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Entregas por obrigação (Relatório)</h1>
            <p class="lead"> 
                <a href="{{ route('dashboard') }}">Voltar</a>  
            </p>
        </div>
    </div>
</div>
<div class="table-default table-responsive">
    <table class="table display" id="entregas-table">
        <thead>
            <tr class="top-table">
                <th>Data Limite</th>
                <th>Descrição</th>
                <th>CNPJ</th>
                <th>Cód. Município</th>
                <th>Tributo</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (!empty($retval)) { 
            foreach ($retval as $key => $value) { ?>
            <tr>
                <td><?php echo $value['limite']; ?></td>
                <td><?php echo $value['descricao']; ?></td>
                <td><?php echo $value['cnpj']; ?></td>
                <td><?php echo $value['codigo']; ?></td>
                <td><?php echo $value['nome']; ?></td>
            </tr>
        <?php } } ?>
        </tbody>
    </table>
</div>
<script>

$(function() {
    $('#entregas-table').DataTable({
        stateSave: true,
        responsive: true,
        language: {
            "searchPlaceholder": "Buscar"
        },
    
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
        ]
    });
});

</script>

@stop

