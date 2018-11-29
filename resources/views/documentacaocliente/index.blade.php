@extends('...layouts.master')

@section('content')


<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Documentos</h1>
        </div>
    </div>
</div>

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif


<div class="modal fade" id="myModalUpload" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Upload de Documento</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="observacaoHTML" style="width: 100%; height: 100%;">
        {!! Form::open([
            'route' => 'documentacao.upload',
            'enctype' => 'multipart/form-data'
        ]) !!}   

         <div class="control-group">
          <div class="controls">
              {!! Form::file('image', array('class'=>'btn btn-default ')) !!}
              <input type="hidden" name="id" id="documento_id">
          </div>
        </div>
        <div id="success"></div>
        <br/>
        
      </div>
      <div class="modal-footer">
        {!! Form::submit('Salvar', array('class'=>'btn btn-default ')) !!}
        {!! Form::close() !!}
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

        <div class="table-default table-responsive">
            <table class="table display" id="myTableAprovacao">
                <thead>
                    <tr class="search-table">
                        
                    </tr>
                    <tr class="top-table">
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Data de Criação</th>
                        <th>Autor</th>
                        <th>Data da Última Atualização</th>
                        <th>Atualizado</th>
                        <th>Versão</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @if (!empty($table))
                    @foreach ($table as $key => $value)
                    <tr>
                        <td><?php echo $value->id;?></td>
                        <td><?php echo $value->descricao;?></td>
                        <td><?php 
                                $data = $value->data_criacao;
                                $date_format = date('d-m-Y', strtotime($data));
                                $date = str_replace('-', '/', $date_format);
                                echo $date;
                            ?>    
                        </td>
                        <td><?php echo $value->autor->name;?></td>
                        <td><?php 
                              $data = $value->data_atualizacao;
                              if (!empty($data)) {
                                $date_format = date('d-m-Y', strtotime($data));
                                $date = str_replace('-', '/', $date_format);
                                echo $date;
                              }
                            ?> 
                        </td>
                        <td><?php if (!empty($value->userAtualiza)) {
                          echo $value->userAtualiza->name;
                        } else {
                          echo "";
                        } ?></td>
                        <td><?php echo $value->versao.'.0';?></td>
                        <td align="center">
                            <a href="{{ route('documentacao.editar', $value->id) }}" class="btn btn-default btn-sm" style="margin: 1px"><i class="fa fa-edit"></i>
                            <a href="{{ route('documentacao.excluir', $value->id) }}" class="btn btn-default btn-sm" style="margin: 1px"><i class="fa fa-trash"></i>
                            <a href="#" onclick="fileUpload(<?php echo $value->id;?>)" class="btn btn-default btn-sm" style="margin: 1px"><i class="fa fa-upload"></i></a>
                            <a href="{{ route('documentacao.download', $value->id) }}" class="btn btn-default btn-sm" style="margin: 1px"><i class="fa fa-download"></i></a>
                        </td>
                    </tr>
                    @endforeach
                @endif 
                </tbody>
            </table>
        </div>
<script>


$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Pesquisar registro específico",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "lfrtip",
        processing: true,
        stateSave: true,
        language: {
        "searchPlaceholder": "ID, P.A. ou descrição"
        //"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
         },
         dom: 'l<"centerBtn"B>frtip',
         buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
         ],
         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });        
});


$('#sidebar').toggleClass('active');
$('#sidebarCollapse').toggleClass('auto-left');
$('#content').toggleClass('auto-left');


function fileUpload(id)
{   
    $("#documento_id").val(id);
    $("#myModalUpload").modal(); 
}

</script>

@stop

