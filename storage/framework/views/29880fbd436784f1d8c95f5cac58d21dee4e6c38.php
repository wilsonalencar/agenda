<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-9">
        <h1>INNO<span style="color:red">V</span>AGENDA</h1>
        <p class="lead">Devido ao alto volume de estabelecimentos, localizados em áreas diferentes, existe uma
        complexidade do controle de todas as entregas tributárias a ser efetuadas no ano fiscal. Por este motivo,
        identificou-se a necessidade de construir uma ferramenta que ajude o time no gerenciamento das datas de
        entrega para torná-lo mais eficiente e, ao mesmo tempo, minimizar o risco de erros ou atrasos.</p>
        <img src="<?php echo e(URL::to('/')); ?>/assets/img/agenda-fiscal.png" /><br/>
        <hr>
    </div>
    <div class="col-md-3">
        <?php if(!Auth::guest()): ?>
        <div class="row">
            <h3>ONLINE</h3>
            <?php foreach($users as $user): ?>
                <?php if($user->isOnline()): ?>
                    <img src="<?php echo e(URL::to('/')); ?>/assets/img/<?php echo e($user->roles[0]->name); ?>-icon.png" title="<?php echo e($user->name); ?>" />
                    <!--i title="<?php echo e($user->name); ?> online" class="fa fa-user"></i-->
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="row">
            <h3>ENTREGAS</h3>
            <table>
            <thead>
                <th>Usuário</th>
                <th colspan="2">Entregas/Prazo</th>
            </thead>
            <?php foreach($standing as $user): ?>
            <tr>
               <td style="font-size:6;"><?php echo e($user->name); ?></td>
               <td><i style="padding: 0px 5px 0px 5px" class="fa fa-info" title="<?php echo e($user->entrega_em_prazo); ?> de <?php echo e($user->entregas_totais); ?>"></i></td>
               <td style="font-size:6; color:#333; text-align:right"><?php echo e($user->perc); ?> %</td>
            </tr>
            <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<hr/>
<div style="text-align: center">
    <img style="height:40px" src="<?php echo e(URL::to('/')); ?>/assets/img/innova_logo_small.png" />
    <div style="font-size: small; font-weight: bold">Agenda Fiscal Versão 2.0 - COPYRIGHT © 2016 - Developed by Eng.F.Sbaratta, powered by AZURE</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>