<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <div style="display:inline; position:relative; top:5px; font-family:'Arial', 'Helvetica', sans-serif; font-size:26px;"><span>Inno</span><img style="height:20px; margin-bottom: 5px" src="<?php echo e(URL::to('/')); ?>/assets/img/v_logo_small.png" /><span>agenda</span></div>
    </div>
     <!-- Right Side Of Navbar -->
    <ul id="main-menu" class="sm sm-clean navbar-right">
        <?php if(Auth::guest()): ?>
        <li><a href="<?php echo e(url('/login')); ?>"><i class="fa fa-btn fa-sign-in"></i> Login</a></li>
        <li><a href="<?php echo e(url('/register')); ?>"><i class="fa fa-btn fa-user"></i> Cadastre-se</a></li>
        <?php else: ?>
        <li><a href="<?php echo e(route('home')); ?>"><i class="fa fa-btn fa-home"></i></a></li>
        <li><a href="#">|</a></li>
        <li><a href="<?php echo e(route('about')); ?>"><i class="fa fa-btn fa-info"></i> Info</a></li>
        <li><a href="#">|</a></li>
            <?php if( Auth::user()->hasRole('user') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor')): ?>
                <li><a href="<?php echo e(route('calendario')); ?>"><i class="fa fa-btn fa-calendar"></i> Calendário</a></li>
                <li><a href="#">|</a></li>
            <?php endif; ?>
            <?php if( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin')): ?>
                 <li><a href="#"><i class="fa fa-btn fa-table"></i> Cargas</a>
                        <ul>
                            <li></li>
                            <li><a href="<?php echo e(route('cargas')); ?>"><i class="fa fa-btn fa-file-text-o"></i> Por Estabelecimento</a></li>
                            <li><a href="<?php echo e(route('cargas_grafico')); ?>"><i class="fa fa-btn fa-file-text-o"></i> Visualização Grafica</a></li>
                        </ul>
                 </li>
                 <li><a href="#">|</a></li>
            <?php endif; ?>
            <?php if( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner')): ?>
                <li><a href="<?php echo e(route('entregas.index')); ?>"><i class="fa fa-btn fa-upload"></i> Entrega</a></li>
                <li><a href="#">|</a></li>
            <?php endif; ?>
            <?php if( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner')): ?>
                <li><a href="<?php echo e(route('arquivos.index')); ?>"><i class="fa fa-btn fa-paperclip"></i> Arquivo</a></li>
                <li><a href="#">|</a></li>
            <?php endif; ?>
            <?php if( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor')): ?>
                <li><a href="#"><i class="fa fa-btn fa-table"></i> Configurações</a>
                    <ul>
                        <li></li>
                        <?php if( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner')): ?>
                        <li><a href="<?php echo e(route('categorias.index')); ?>">Categorias Fiscais</a></li>
                        <li><a href="<?php echo e(route('tributos.index')); ?>">Tributos</a></li>
                        <li><a href="<?php echo e(route('regras.index')); ?>">Regras</a></li>
                        <li><a href="<?php echo e(route('usuarios.index')); ?>">Usuários</a></li>
                        <li><a href="<?php echo e(route('atividades.index')); ?>">Atividades</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo e(route('empresas.index')); ?>">Empresas</a></li>
                        <li><a href="<?php echo e(route('estabelecimentos.index')); ?>">Estabelecimentos</a></li>
                        <li><a href="<?php echo e(route('municipios.index')); ?>">Municipios</a></li>
                        <li><a href="<?php echo e(route('feriados')); ?>">Feriados</a></li>
                    </ul>
                </li>
                <li><a href="#">|</a></li>
            <?php endif; ?>
        <li><a href="<?php echo e(url('/logout')); ?>">
            <?php if(!Auth::guest()): ?>
            <img style="height:16px;padding-bottom: 2px" src="<?php echo e(URL::to('/')); ?>/assets/img/<?php echo e(Auth::user()->roles()->first()->name); ?>-icon.png" title="<?php echo e(Auth::user()->roles()->first()->display_name); ?>" /> (<?php echo e(Auth::user()->name.' '); ?>)
            <?php endif; ?>
            <i class="fa fa-btn fa-sign-out"></i>Logout</a>
        </li>
        <?php endif; ?>
    </ul>
  </div>
</nav>
<script type="text/javascript">

        $(function() {
            $('#main-menu').smartmenus({
                subMenusSubOffsetX: 1,
                subMenusSubOffsetY: -8
            });
        });
</script>