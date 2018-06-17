@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'guiaicms.planilha'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Período de busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">     
                {!! Form::label('inicio', 'Data Inicial', ['class' => 'control-label']) !!}    
                {!! Form::date('inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">         
            {!! Form::label('fim', 'Data Final', ['class' => 'control-label']) !!}
                {!! Form::date('fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="row">
            <br />
        </div>
        <div class="row">
            <div class="col-md-2">
                
    <table class="table table-bordered display" id="dataTables-example" style="width: 100%; height: 100%; font-size: 12px; display: none;">
    <thead>
    <tr style="display: none">
        <th>CAB_CDRCIN</th>
        <th>CAB_CODTBT</th>
        <th>CAB_BUKRS</th>
        <th>CAB_BARCOD</th>
        <th>CAB_DTVENC</th>
        <th>CAB_GSBER</th>
        <th>CAB_CNPJE</th>
        <th>CAB_COMPCM</th>
        <th>CAB_COMENT</th>
        <th>CAB_RGINST</th>
        <th>CAB_NFENUM</th>
        <th>CAB_SERIES</th>
        <th>CAB_SUBSER</th>
        <th>CAB_ACCESS_KEY</th>
        <th>CAB_AUTHCOD</th>
        <th>CAB_DATANF</th>
        <th>CAB_FGTSID</th>
        <th>CAB_AUFNR</th>
        <th>RAT_KOSTL</th>
        <th>RAT_GSBER</th>
        <th>RAT_VALOR</th>
        <th>RAT_VAL_ATU</th>
        <th>RAT_VAL_MULTA</th>
        <th>RAT_VAL_JUROS</th>
        <th>RAT_VAL_OUTROS</th>
        <th>RAT_VAL_ACRES</th>
        <th>RAT_VAL_DESCONT</th>
        <th>RAT_AUFNR</th>
    </tr>
    </thead>
        <tbody>
        <?php
            if (!empty($planilha)) {
              foreach ($planilha as $key => $value) {  
        ?>
            <tr style="display: none">
                <td><?php echo $value['uf'];?></td>
                <td>SEFAZ</td>
                <td><?php if (substr($value['CNPJ'], 0,8)) {
                    echo "1000";
                } ?></td>
                <td><?php echo $value['CODBARRAS'];?></td>
                <?php
                $valorData = $value['DATA_VENCTO'];
                $data_vencimento = str_replace('-', '/', $valorData);
                $value['DATA_VENCTO'] = date('d/m/Y H:i:s', strtotime($data_vencimento));
                ?>
                <td><?php echo $value['DATA_VENCTO'];?></td>
                <td><?php echo $value['codigo'];?></td>
                <td></td>
                <td></td>
                <td><?php echo 'Pagto ICMS'.$value['codigo'].'/'.$value['centrocusto'];?></td>
                <td></td>
                <td>ICMS</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><?php echo $value['centrocusto'];?></td>
                <td><?php echo $value['codigo'];?></td>
                <td><?php echo $value['VLR_TOTAL'];?></td>
                <td></td>
                <td><?php echo $value['MULTA_MORA_INFRA'];?></td>
                <td><?php echo $value['JUROS_MORA'];?></td>
                <td></td>
                <td><?php echo $value['ACRESC_FINANC'];?></td>
                <td></td>
                <td></td>
            </tr>
        <?php } } ?>
                
        </tbody>
    </table>
            </div>
            <div class="col-md-2">
                {!! Form::submit('Gerar', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>

<script type="text/javascript">
    
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

$(document).ready(function () {
    $('#dataTables-example').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        name: 'oii',
        <?php
        if (!empty($planilha)) {
        ?>
        buttons: [
            {
                extend: 'excelHtml5',
                title: 'ZFIC_COMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>'
            },
            {
                extend: 'csvHtml5',
                title: 'ZFIC_COMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>'
            }
        ]
        <?php }?>
    });     
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>