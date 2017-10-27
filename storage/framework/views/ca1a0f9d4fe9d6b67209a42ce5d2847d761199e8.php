<?php $__env->startSection('content'); ?>
<h2>Dashboard Analista</h2>
<hr>
<?php echo Form::open([
    'route' => 'dashboard_analista'
]); ?>


<div class="form-group">
    <div class="row">
        <div class="col-md-2">
        <?php echo Form::label('uf', 'UF:', ['class' => 'control-label']); ?>

        <?php echo Form::select('uf', $ufs, $graph['params']['p_uf'], ['class' => 'form-control','placeholder' => 'Seleciona...']); ?>

        </div>
        <div id="codigo_input" class="col-md-5">
        <?php echo Form::label('codigo', 'Municipio:', ['class' => 'control-label']); ?>

        <?php echo Form::select('codigo', $municipios, null, ['class' => 'form-control']); ?>

        </div>
        <div class="col-md-2">
            <?php echo Form::label('tributo', 'Tributo:', ['class' => 'control-label']); ?>

            <?php echo Form::select('tributo', $tributos, $graph['params']['p_tributo'], ['class' => 'form-control','placeholder' => 'Todos']); ?>

        </div>
        <div class="col-md-2">
            <?php echo Form::label('codigo', 'Periodo Apuração:', ['class' => 'control-label']); ?>

            <div class="input-group spinner">
                <input type="text" class="form-control" value="<?php echo e(substr($periodo,0,2)); ?>/<?php echo e(substr($periodo,-4,4)); ?>">
                <div class="input-group-btn-vertical">
                  <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                  <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                </div>
            </div>
        </div>
    </div>
    <div class="row"><br/></div>
    <div class="row">
        <div class="col-md-2">
        <?php echo Form::label('only-uf', 'Somente por UF?', ['class' => 'control-label']); ?>

        <?php echo Form::checkbox('only-uf',1,$graph['params']['p_onlyuf'], ['class' => 'form-control','style' => 'width:30px']); ?>

        </div>
    </div>
</div>

<?php echo Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']); ?>

<?php echo Form::submit('Atualizar', ['id' => 'atualiza_btn', 'class' => 'btn btn-default']); ?>

<?php echo Form::close(); ?>


<div id="container" style="position:absolute; right:150px; top:250px;">dashboard-analista</div>

<script>
$(function () {

    var tot_status_1 = <?php echo e(($graph['status_1'])); ?>;
    var tot_status_2 = <?php echo e(($graph['status_2'])); ?>;
    var tot_status_3 = <?php echo e(($graph['status_3'])); ?>;
    var tot = tot_status_1+tot_status_2+tot_status_3;

    $('#container').highcharts({
        chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                        text: 'Status Entregas'

                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Entregas (efet./total): <b>{point.y} / {point.total}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Percentual entregas',
                    colorByPoint: true,
                    data: [{
                        name: 'Não efetuada',
                        y: tot_status_1
                    }, {
                        name: 'Em aprovação',
                        y: tot_status_2,
                        sliced: true,
                        selected: true
                    }, {
                        name: 'Aprovada',
                        y: tot_status_3
                    }]
                }]
    });
//setInterval(function(){ $( '#atualiza_btn' ).click() }, 300000);



});

function retriveMunicipios(uf,cod) {

    $.get("<?php echo e(url('/dropdown-municipios')); ?>",

                { option: uf },

                   function(data) {
                       var model = $('#codigo');
                       model.empty();

                       $.each(data, function(index, element) {
                           model.append("<option value='"+ element.codigo +"'>" + element.nome + "</option>");
                       });

                       $('#codigo').val(cod).attr("selected", "selected"); //Reload codigo
                   }

    ); //Reload last list

}

jQuery(document).ready(function($){

    $('#only-uf').click (function(){

      if ( $(this).is(':checked') ) {
        $('#codigo_input').hide();
      } else {
        $('#codigo_input').show();
      }
    });

    <?php if($graph['params']['p_uf']): ?>
        retriveMunicipios('<?php echo e($graph['params']['p_uf']); ?>','<?php echo e($graph['params']['p_codigo']); ?>');
    <?php endif; ?>
    <?php if($graph['params']['p_onlyuf']): ?>
        $('#codigo_input').hide();
    <?php endif; ?>
	$('#uf').change(function(){

        $.get("<?php echo e(url('/dropdown-municipios')); ?>",

            { option: $(this).val() },

            function(data) {
                var model = $('#codigo');
                model.empty();

                $.each(data, function(index, element) {
                    model.append("<option value='"+ element.codigo +"'>" + element.nome + "</option>");
             });
        });
    });


     $('.spinner .btn:first-of-type').on('click', function() { //UP
            var value = $('.spinner input').val();

            var mes = parseInt(value.substr(0,2));
            var year = parseInt(value.substr(3,4));
            mes += 1;
            if (mes>12) {
                mes = 1;
                year += 1;
            } else if (mes<10) {
                mes = '0'+mes;
            }
            year = ''+year;
            $('.spinner input').val(mes+'/'+year);

            $('input[name="periodo_apuracao"]').val(mes+year);
            $( "#atualiza_btn" ).click();

      });

     $('.spinner .btn:last-of-type').on('click', function() {  //DOWN
            var value = $('.spinner input').val();

            var mes = parseInt(value.substr(0,2));
            var year = parseInt(value.substr(3,4));
            mes -= 1;
            if (mes<1) {
                mes = 12;
                year -= 1;
            } else if (mes<10) {
                mes = '0'+mes;
            }
            year = ''+year;
            $('.spinner input').val(mes+'/'+year);

            $('input[name="periodo_apuracao"]').val(mes+year);
            $( "#atualiza_btn" ).click();
     });

     $('#tributo').change(function(){
            $( "#atualiza_btn" ).click();
     });
});
</script>


<?php $__env->stopSection(); ?>
<footer>
   <?php echo $__env->make('layouts.footer', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
</footer>


<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>