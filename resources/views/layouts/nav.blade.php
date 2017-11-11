<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <div style="display:inline; position:relative; top:5px; font-family:'Arial', 'Helvetica', sans-serif; font-size:21px;"><span><font color="#B22222"><b>B</b></font>ravo - Tax Calendar</span> <!--- <img style="height:20px; margin-bottom: 5px" src="{{ URL::to('/') }}/assets/img/v_logo_small.png" /><span>agenda</span>--></div>
    </div>
     <!-- Right Side Of Navbar -->
    <ul id="main-menu" class="sm sm-clean navbar-right">
            

            @if (Auth::guest())
            <li><a href="{{ url('/login') }}"><i class="fa fa-btn fa-sign-in"></i> Login</a></li>
            @else

            <?php if (!empty(session()->get('seid'))){ ?> 
                <li><a href="{{ route('home') }}"><i class="fa fa-btn fa-home"></i></a></li>
                
                @if ( Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                    <li><a href="#">|</a></li>
                    <li><a href="{{ route('about') }}"><i class="fa fa-btn"></i>Tax Cockpit Calendar</a></li>
                @endif
                <li><a href="#">|</a></li>
                    @if ( Auth::user()->hasRole('user') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor'))
                        <li><a href="{{ route('calendario') }}"><i class="fa fa-btn fa-calendar"></i> Calendário</a></li>
                        <li><a href="#">|</a></li>
                    @endif

                    @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                         <li><a href="#"><i class="fa fa-btn fa-table"></i>Tax Calendar</a>
                                <ul>
                                    <li></li>
                                    <li>
                                        <a href="#"><i class="fa fa-btn fa-file-text-o"></i>Conta Corrente</a>
                                        <ul>
                                                <li></li>
                                                <li><a href="{{ route('movtocontacorrentes.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                <li><a href="{{ route('movtocontacorrentes.search') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                <li><a href="{{ route('movtocontacorrentes.import') }}"><i class="fa fa-btn fa-file-text-o"></i>Importar</a></li>
                                            </ul>
                                    </li>
                                    <li>
                                        <a href="#"><i class="fa fa-btn fa-file-text-o"></i>Processos Administrativos</a>
                                        <ul>
                                                <li></li>
                                                <li><a href="{{ route('processosadms.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                <li><a href="{{ route('processosadms.search') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                <li><a href="{{ route('processosadms.import') }}"><i class="fa fa-btn fa-file-text-o"></i>Importar</a></li>
                                            </ul>
                                    </li>
                                    
                                    @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                                     <li><a href="#"><i class="fa fa-btn fa-table"></i>Integrações</a>
                                            <ul>
                                                <li></li>
                                                <li><a href="{{ route('cargas') }}"><i class="fa fa-btn fa-file-text-o"></i> Por Estabelecimento</a></li>
                                                <li><a href="{{ route('cargas_grafico') }}"><i class="fa fa-btn fa-file-text-o"></i> Visualização Grafica</a></li>
                                            </ul>
                                     </li>
                         
                                    @endif
                                </ul>

                         </li>
                         <li><a href="#">|</a></li>
                    @endif


                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                        <li><a href="#"><i class="fa fa-btn fa-table"></i>Tax Workflow Manager</a>
                            <ul>
                                <li></li>
                                <li><a href="{{ route('entregas.index') }}"><i class="fa fa-btn fa-upload"></i>Entregas</a></li>
                            </ul>
                        <li><a href="#">|</a></li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                        <li><a href="#"><i class="fa fa-btn fa-table"></i>Tax Repository</a>
                            <ul>
                                <li></li>
                                <li><a href="{{ route('arquivos.index') }}"><i class="fa fa-btn fa-upload"></i>Arquivos</a></li>
                            </ul>
                        <li><a href="#">|</a></li>
                    @endif
                    <?php } ?>

                    @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                    <li><a href="#"><i class="fa fa-btn fa-table"></i> Tax Configuration</a>
                        <ul>
                            <li></li>
                            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                            <li><a href="{{ route('categorias.index') }}">Categorias Fiscais</a></li>
                            <li><a href="{{ route('tributos.index') }}">Tributos</a></li>
                            <li><a href="{{ route('regras.index') }}">Regras</a></li>
                            <li><a href="javascript:void(0);">Usuários</a>
                                <ul>
                                    <li></li>
                                    <li><a href="{{ route('usuarios.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                    <li><a href="{{ route('usuarios.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                </ul>
                            </li>
                            <li><a href="{{ route('atividades.index') }}">Atividades</a></li>
                            @endif
                            <li><a href="{{ route('empresas.index') }}">Empresas</a></li>
                            <?php if (!empty(session()->get('seid'))){ ?> 
                                <li><a href="{{ route('estabelecimentos.index') }}">Estabelecimentos</a></li>
                            <?php } ?>
                            <li><a href="{{ route('municipios.index') }}">Municipios</a></li>
                            <li><a href="{{ route('feriados') }}">Feriados</a></li>
                            <li><a href="{{ route('mensageriaprocadms.create') }}">Mensageria Processo Administrativo</a></li>
                        </ul>
                    </li>
                    <li><a href="#">|</a></li>
                @endif
            <li><a href="{{ route('home', 'selecionar_empresa', '1') }}">Selecionar Empresa</a></li>
             <li><a href="#">|</a></li>
            <li><a href="{{ url('/logout') }}">
                @if (!Auth::guest())
                <img style="height:16px;padding-bottom: 2px" src="{{ URL::to('/') }}/assets/img/{{ Auth::user()->roles()->first()->name }}-icon.png" title="{{ Auth::user()->roles()->first()->display_name }}" /> ({{ Auth::user()->name.' ' }})
                @endif
                <i class="fa fa-btn fa-sign-out"></i>Logout</a>
            </li>
                @endif
            

        
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
