@extends('...layouts.master')

@section('content')

<h1>Cargas</h1>
<div class="row">
    <div class="col-md-7">
        <p class="lead">Status de carga para todos os estabelecimentos ativos.</p>
    </div>
    <div title="" class="col-lg-3">
        <input type="checkbox" name="switch-checkbox" <?= $switch==1?'checked':'' ?> >
    </div>
    <div class="col-md-1">
        {!! Form::open([
                    'route' => 'cargas'
        ]) !!}
        {!! Form::hidden('switch_val', $switch, ['class' => 'form-control']) !!}
        {!! Form::button('<i class="fa fa-refresh"></i> Atualizar', array('id' => 'atualiza_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
    @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
    <div class="col-md-1">
        {!! Form::open([
            'route' => 'cargas.reset'
        ]) !!}
        {!! Form::button('<i class="fa fa-repeat"></i> Reset', array('id' => 'reset_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
    @endif
</div>
<hr>
<table class="table table-bordered display" id="estabelecimentos-table">
    <thead>
    <tr>
        <th>CÓDIGO</th>
        <th>CNPJ</th>
        <th>UF</th>
        <th>CARGA ENTRADA</th>
        <th>CARGA SAÍDA</th>
    </tr>
    </thead>
</table>
<script>

$(function() {

    setInterval(function(){ $( '#atualiza_btn' ).click() }, 60000);

    $.fn.bootstrapSwitch.defaults.onText = 'CARREGADOS';
    $.fn.bootstrapSwitch.defaults.offText = 'TODOS';
    $("[name='switch-checkbox']").bootstrapSwitch();

    $('input[name="switch-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

      $('input[name="switch_val"]').val(state?1:0);
      $("body").css("cursor", "progress");
      $( "#atualiza_btn" ).click();
    });

    // Select the submit buttons of forms with data-confirm attribute
    var reset_button =$( '#reset_btn' );

    // On click of one of these submit buttons
    reset_button.on('click', function (e) {

        // Prevent the form to be submitted
        e.preventDefault();

        var button = $(this); // Get the button
        var form = button.closest('form'); // Get the related form
        var msg = 'Quer realmente zerar as informações de carga?'; // Get the confirm message

        if(confirm(msg)) form.submit(); // If the user confirm, submit the form
        $("body").css("cursor", "progress");

    });

    $( "#atualiza_btn" ).click(function() {
        $("body").css("cursor", "progress");
    });

    $('#estabelecimentos-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('cargas.data') !!}",
                data: function (d) {
                    d.ativo = $("[name='switch-checkbox']").is( ":checked" )?1:0;
                }
            },
        columnDefs: [{ "width": "80px", "targets": 3 },{ "width": "80px", "targets": 4 }],
        columns: [
            {data: 'codigo', name: 'codigo'},
            {data: 'cnpj', name: 'cnpj',render: function ( data ) {
                                                      return printMaskCnpj(data);
                                                    }},
            {data: 'municipio.uf', name: 'municipio.uf', orderable: false},
            {data: 'id', name:'carga_entrada', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                      url += '<a href="{{ route('cargas.changeStateEntrada', array('0', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga entrada efetuada" /></a>';
                                                                  } else {
                                                                      url += '<a href="{{ route('cargas.changeStateEntrada', array('1', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga entrada não efetuada" /></a>';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                        url += '<img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga entrada efetuada" />';
                                                                    } else {
                                                                        url += '<img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga entrada não efetuada" />';
                                                                    }
                                                                  @endif
                                                                  return url;
            }},
            {data: 'id', name:'carga_saida', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_saida']==1) {
                                                                        url += '<a href="{{ route('cargas.changeStateSaida', array('0', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga saída efetuada" /></a>';
                                                                  } else {
                                                                        url += '<a href="{{ route('cargas.changeStateSaida', array('1', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga saída não efetuada" /></a>';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                    if(row['carga_msaf_saida']==1) {
                                                                          url += '<img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga saida efetuada" />';
                                                                      } else {
                                                                          url += '<img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga saida não efetuada" />';
                                                                      }
                                                                    @endif
                                                                  return url;
            }}
        ],
         language: {
                                    //"searchPlaceholder": "ID, P.A. ou descrição",
                                    "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
         },
         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
         dom: 'l<"centerBtn"B>frtip',
         buttons: [
              'copyHtml5',
              'excelHtml5'
         ]

    });

});

</script>

@stop
