@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'guiaicms.conferencia'
]) !!}
<?php if (@!empty($mensagem)) { ?>
    <div class="alert alert-success">
        <?php echo $mensagem; ?>
    </div>
<?php } ?>
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Período de busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                {!! Form::label('multiple_select_tributos[]', 'Estabelecimentos', ['class' => 'control-label'] )  !!}
                <select multiple="multiple" name="multiple_select_estabelecimentos[]" id="estabelecimentos" class="form-control s2_multi">
                <?php foreach($estabelecimentos as $aKey => $value) { 
                    $selected = false;
                    foreach($estabelecimentosselected as $key) {
                        if($aKey == $key) {
                            $selected = true;
                        }
                    }
                ?>
                    <option value="{{$aKey}}" @if($selected)selected="selected"@endif>{{$value}}</option>
                <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                {!! Form::label('multiple_select_tributos[]', 'UF', ['class' => 'control-label'] )  !!}
                {!!  Form::select('multiple_select_uf[]', $uf, $ufselected, ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
            <div class="col-md-2">     
                {!! Form::label('inicio', 'Data Inicial', ['class' => 'control-label']) !!}    
                {!! Form::date('inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">         
            {!! Form::label('fim', 'Data Final', ['class' => 'control-label']) !!}
                {!! Form::date('fim', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">
            <br />
                {!! Form::submit('Gerar', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
        <hr />
        <div class="row">
            <table class="table table-bordered display" id="dataTables-example" style="width: 100%; font-size: 12px;">
            <thead>
            <tr>
                <th>Filial</th>
                <th>CNPJ</th>
                <th>IE</th>
                <th>UF</th>
                <th>Código Receita</th>
                <th>Referencia</th>
                <th>Vencimento</th>
                <th>Vlr Receita</th>
                <th>Vlr Total</th>
                <th>Código de Barras</th>
                <th>Tipo Imposto</th>
            </tr>
            </thead>
                <tbody>
                <?php
                    if (!empty($planilha)) {
                ?>
                <?php
                      foreach ($planilha as $key => $value) {  
                ?>
                    <tr>
                        <td><?php echo $value['codigo']; ?></td>
                        <td><?php echo $value['CNPJ']; ?></td>
                        <td><?php echo $value['IE']; ?></td>
                        <td><?php echo $value['UF']; ?></td>
                        <td><?php echo $value['COD_RECEITA']; ?></td>
                        <td><?php echo $value['REFERENCIA']; ?></td>
                        <td><?php echo $value['DATA_VENCTO']; ?></td>
                        <td><?php echo $value['VLR_RECEITA']; ?></td>
                        <td><?php echo $value['VLR_TOTAL']; ?></td>
                        <td><?php echo $value['CODBARRAS']; ?></td>
                        <td><?php echo $value['IMPOSTO']; ?></td>
                    </tr>
                <?php } } ?>        
                </tbody>
            </table>
            <br> 
        </div>
    </div>

<script type="text/javascript">
    
$('select').select2();

$(document).ready(function () {
    $('#dataTables-example').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "<B>frtip",
        <?php
        if (!empty($planilha)) {
        ?>
        buttons: [
             {
                extend: 'csvHtml5',
                exportOptions: {
                   columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                }
             },
             {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
            }
        ],
        "ordering": false
        <?php }?>
    });     
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>