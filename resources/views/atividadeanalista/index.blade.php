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
<hr>
   <table class="table table-bordered display" id="myTableAprovacao">   
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Analista</th>
                <th>Tributo</th>
                <th>Estabelecimento</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @if (!empty($table))
            @foreach ($table as $key => $value)  

            <tr>
               <td><?php echo $value['razao_social']; ?></td>
               <td><?php echo $value['name']; ?></td>
               <td><?php echo $value['nome']; ?></td>
               <td><?php echo $value['estabelecimento']; ?></td>
               <td><a href="{{ route('atividadesanalista.editRLT', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
               </td>
            </tr> 
            @endforeach
        @endif 
        </tbody>
    </table>                                            

<script type="text/javascript">
    $(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        //"searchPlaceholder": "ID, P.A. ou descrição",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        processing: true,
        stateSave: true,
        order: [[ 0, 'asc' ], [ 1, 'asc' ]],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });        
});

</script>
@stop