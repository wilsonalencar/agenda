<?php $__env->startSection('content'); ?>

<?php echo $__env->make('partials.alerts.errors', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

<div style="float:right" class="detailBox">
          <div class="titleBox">
            <label>Comentários sobre a atividade</label>
          </div>
          <div class="commentBox">
              <p class="taskDescription">Nesta caixa são armazenados os comentários efetuados pelos usuários sobre esta atividade.</p>
          </div>
          <div class="actionBox">
              <ul class="commentList">
                  <?php foreach($atividade->comentarios as $el): ?>
                  <li>
                      <div>
                        <p class="commenterName"><?php echo e($el->user->name); ?></p><p class="commentText"><?php echo e($el->obs); ?></p> <span class="date sub-text"> (<?php echo e($el->created_at); ?>)</span>
                      </div>
                  </li>
                  <?php endforeach; ?>
              </ul>

          </div>
      </div>

<div class="about-section">
   <div class="text-content">
     <div class="span7 offset1">

        <h2>Entrega recibo atividade</h2>
        <h3>Tributo: <?php echo $atividade->regra->tributo->nome; ?> - Descrição: <?php echo $atividade->regra->nome_especifico; ?> <?php echo $atividade->descricao; ?></h3>
        <h3>Periodo Apuração: <?php echo $atividade->periodo_apuracao; ?></h3>
        <h3>Estabelecimento: <?php echo e(mask($atividade->estemp->cnpj,'##.###.###/####-##')); ?></h3><br/>
        <small>Data limite para entrega: <?php echo e(Date_Converter($atividade->limite)); ?></small><br/>
        <small>Data atual: <?php echo e(Date_Converter(date('Y-m-d H:m:s'))); ?></small><br/>
        <br/>
        <span>O documento será inserido no workflow de processo para aprovação do responsável.</span>
        <br/>
        <hr/>
        <?php if(Session::has('success')): ?>
          <div class="alert-box success">
          <h2><?php echo Session::get('success'); ?></h2>
          </div>
        <?php endif; ?>
        <?php echo Form::open(array('url'=>'upload/sendUpload','method'=>'POST', 'files'=>true)); ?>

         <div class="control-group">
          <div class="controls">
                <?php echo Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']); ?>

                <?php echo Form::file('image', array('class'=>'btn btn-default ')); ?>


                <?php if(Session::has('error')): ?>
                    <p style="color:red; font-weight: bold" class="errors"><?php echo Session::get('error'); ?></p>
                <?php endif; ?>
          </div>
        </div>
        <div id="success"> </div>
        <br/>
        <?php echo Form::submit('Envio com documentação', array('class'=>'btn btn-default ')); ?>

        <?php echo Form::close(); ?>

      </div>
      <hr>
      <div>
        <?php echo Form::open([
            'route' => 'atividades.storeComentario'
        ]); ?>


            <?php echo Form::label('obs', 'Comentario (max.120 caracteres)', ['class' => 'control-label']); ?>

            <?php echo Form::textarea('obs', null, ['style'=> 'width:500px; height:50px','class' => 'form-control']); ?>

            <?php echo Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']); ?>

            <?php echo Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control']); ?>

            <br/>
            <?php echo Form::submit('Adiciona comentario', ['name'=>'com','class' => 'btn btn-default']); ?>

            <?php echo Form::submit('Adiciona comentario e envia sem documentação', array('name'=>'esd','class'=>'btn btn-default ')); ?>

            <?php echo Form::close(); ?>


        <?php echo Form::close(); ?>


        <br/>
      </div>
   </div>
</div>
<?php
function mask($val, $mask)
{
     $maskared = '';
     $k = 0;
     for($i = 0; $i<=strlen($mask)-1; $i++)
     {
     if($mask[$i] == '#')
     {
     if(isset($val[$k]))
     $maskared .= $val[$k++];
     }
     else
     {
     if(isset($mask[$i]))
     $maskared .= $mask[$i];
     }
     }
     return $maskared;
}
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}

?>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('...layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>