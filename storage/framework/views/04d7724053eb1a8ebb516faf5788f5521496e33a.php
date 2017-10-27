<?php $__env->startSection('content'); ?>
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
<div style="float:left; width:60%">
    <h2>ATIVIDADE</h2><small>(REF #<?php echo e($atividade->id); ?>)</small>
    <p class="lead">Emp/Est: <b><?php echo e($atividade->estemp->codigo); ?></b> - <?php echo e(mask($atividade->estemp->cnpj,'##.###.###/####-##')); ?></p>
    <p class="lead">IE: <?php echo e($atividade->estemp->insc_estadual); ?></p>
    <p class="lead">Descrição: <b><?php echo e($atividade->descricao); ?></b></p>
    <p class="lead">Status: <b><?php echo e(status_label($atividade->status)); ?></b>
    <?php if($atividade->limite < date('Y-m-d')): ?>
    <span style="color:red"> VENCIDA!</span>
    <?php endif; ?>
    </p>
    <?php if($atividade->status >1): ?>
    <p class="lead">Data entrega: <b><?php echo e(substr($atividade->data_entrega,0,10)); ?></b></p>
        <?php if($atividade->status >2): ?>
            <p class="lead">Data aprovação: <b><?php echo e(substr($atividade->data_aprovacao,0,10)); ?></b></p>
        <?php endif; ?>
    <?php else: ?>
    <p class="lead">Data limite entrega: <b><?php echo e(substr($atividade->limite,0,10)); ?></b></p>
    <?php endif; ?>

    <?php if($atividade->obs != null): ?>
    <p class="lead">Comentário: <b><?php echo e($atividade->obs); ?></b></p>
    <?php endif; ?>

    <hr/>
    <?php if( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('analyst')): ?>
        <?php if($atividade->status > 1): ?>
            <?php if($atividade->arquivo_entrega == '-' ): ?>
                <p style="font-weight:bold">Atividade entregue sem documentação.</p>
            <?php else: ?>
                <div class="row">
                    <div class="col-xs-2 col-md-2"><a href="<?php echo e(URL::to('download/'.$atividade->id)); ?>"><img title="Entrega <?php echo e($atividade->data_aprovacao); ?>" src=<?php echo e(asset('assets/img/zip-icon.png')); ?> alt="Logo"></a></div>
                    <?php foreach($atividade->retificacoes as $el): ?>
                    <div class="col-xs-2 col-md-2"><a href="<?php echo e(URL::to('download/'.$el->id)); ?>"><img title="Retificação <?php echo e($el->data_aprovacao); ?>" src=<?php echo e(asset('assets/img/ret-icon.png')); ?> alt="Logo"></a></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    <?php endif; ?>
    <br/>
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

                        <?php echo Form::close(); ?>


                    <?php echo Form::close(); ?>


                    <br/>
        </div>
    <hr/>
    <div class="panel panel-default">
            <div class="panel-heading">Painel Operacional</div>
            <div style="padding:20px" class="panel-body">
                <div class="row">
                    <?php if( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor')): ?>

                        <?php if($atividade->status == 2 && $atividade->entregador->id != Auth::user()->id): ?>
                        <div class="col-md-3">
                            <a href="<?php echo e(route('atividades.aprovar', $atividade->id)); ?>" class="btn-success btn btn-default">Aprovar entrega atividade</a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo e(route('atividades.reprovar', $atividade->id)); ?>" class="btn-danger btn btn-default">Reprovar entrega atividade</a>
                        </div>
                        <?php endif; ?>
                        <?php if($atividade->status == 3 && !Auth::user()->hasRole('supervisor')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo e(route('atividades.retificar', $atividade->id)); ?>" class="btn btn-default">Retificar entrega?</a>
                        </div>
                        <?php endif; ?>

                    <?php endif; ?>
                    <?php if( Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin')): ?>
                    <div class="col-md-3">
                        <?php if($atividade->status == 1): ?>

                                <?php echo Form::open([
                                    'method' => 'DELETE',
                                    'route' => ['atividades.destroy', $atividade->id]
                                ]); ?>

                                    <?php echo Form::submit('Cancelar esta atividade?', ['class' => 'btn btn-default']); ?>

                                <?php echo Form::close(); ?>


                        <?php elseif($atividade->status == 3): ?>

                                <?php echo Form::open([
                                    'method' => 'GET',
                                    'route' => ['atividades.cancelar', $atividade->id]
                                ]); ?>

                                    <?php echo Form::submit('Cancelar entrega?', ['class' => 'btn btn-default']); ?>

                                <?php echo Form::close(); ?>

                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-footer clearfix">
                <div class="pull-right">
                    <a href="<?php echo e(route('atividades.index')); ?>" class="btn btn-default">Voltar</a>
                </div>
            </div>
    </div>
</div>

<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });
</script>
<?php $__env->stopSection(); ?>

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

    function status_label($status) {
        $retval = 'indefinido';
        switch ($status) {
            case 1:
                $retval = 'A entregar';
                break;
            case 2:
                $retval = 'Em aprovação';
                break;
            case 3:
                $retval = 'Aprovada';
                break;
            default:
                break;
        }
        return $retval;
    }
?>
<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>