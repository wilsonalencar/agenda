@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif

<h1>Atividade do Analista</h1>
<p class="lead">Segue a lista de todas as atividades cadastradas.</p>
<a href="{!! route('cronogramaatividades.Loadplanejamento') !!}" class="btn btn-default">Voltar</a>
<hr>
   <table class="table table-bordered display" id="myTableAprovacao">   
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Tributo</th>
                <th>SLA</th>
                <th>Período</th>
                <th>UF</th>
                <th>Qtd Estabelecimento</th>
                <th>Tempo Estabelecimento</th>
                <th>Tempo Total</th>
                <th>Qtd Dias</th>
                <th>Tempo geração</th>
                <th>Qtd Analistas</th>
                <th>Inicio</th>
                <th>Termino</th>

            </tr>
            </thead>
            <tbody>
                <?php if (!empty($dados)) { 
                    foreach ($dados as $chave => $value) {
                ?>  
                <tr>
                    <td><?php echo $value->Empresa_id; ?></td>
                    <td><?php echo $value->Tributo_nome; ?></td>
                    <td><?php echo $value->DATA_SLA; ?></td>
                    <td><?php echo $value->periodo_apuracao; ?></td>
                    <td><?php echo $value->uf; ?></td>
                    <td><?php echo $value->Qtde_estab; ?></td>
                    <td><?php echo $value->Tempo_estab; ?></td>
                    <td><?php echo $value->Tempo_total; ?></td>
                    <td><?php echo $value->Qtd_dias; ?></td>
                    <td><?php echo $value->Tempo_geracao; ?></td>
                    <td><?php echo $value->Qtd_analistas; ?></td>
                    <td><?php echo $value->Inicio; ?></td>
                    <td><?php echo $value->Termino; ?></td>
                </tr>
                <?php } }  ?>
            </tbody>
    </table>                                            

<script type="text/javascript">
$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Pesquisar registro específico",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "lfrtip",
        processing: true,
        stateSave: true
    });        
});

</script>
@stop