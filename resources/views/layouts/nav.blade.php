@if (session('seid') && !Auth::guest())
    <style>
        
        .navbar-brand {
            position: absolute;
            
            
            width: 100%;
            margin-left: -12px;
            margin-top: 5px;
            height: 35px;
            background: url("{{ URL::to('/') }}/assets/logo/logo-{{ session('seid') }}.png") center / contain no-repeat;
        }
    </style>
    
@endif
<div class="side-menu" id="sidebar">
    <nav class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
            <div class="brand-wrapper">
                <button type="button" class="navbar-toggle">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <div class="brand-name-wrapper">
                    <a class="" href="#">
                        <img class="logo" src="{{ URL::to('/') }}/assets/logo/logo.png">
                        <span>Tax Calendar</span><p>
                        @if (session('seidLogo') && !Auth::guest())
                        <div><img src="{{ URL::to('/') }}/assets/logo/Logo-{{ session('seidLogo') }}.png" style="position: absolute; width: 30%; margin-left: 33%; margin-top: 5px; height: 8%;"><br><br><br></div>
                        @endif
                    </a>
                </div>
        </div>

        <!-- Main Menu -->
        <div class="side-menu-container">
            <ul class="nav navbar-nav">

                @if (Auth::guest())
                <li class="active"><a href="{{ url('/login') }}"><i class="fa fa-btn fa-sign-in"></i> Login</a></li>
                @else

                    <li class="active"><a href="{{ route('home', 'selecionar_empresa', '1') }}"><i class="fa fa-btn fa-home"></i>Home</a></li>
                <?php if (!empty(session()->get('seid'))){ ?> 
                    
                    @if ( Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('gbravo') || Auth::user()->hasRole('gcliente'))
                        <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#tax-calendar"><i class="fa fa-dot-circle-o"></i>Cockpit</a>
                                    <div id="tax-calendar" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <ul class="nav navbar-nav">
                                                <li class="panel panel-default" id="dropdown">
                                                    <li><a href="{{ route('home') }}">Entregas Gerais</a></li>
                                                    <li><a href="{{ route('dashboard') }}">Entregas por Obrigação</a></li>
                                                    <li><a href="{{ route('dashboard_analista') }}">Entregas por UF e Municípios</a></li>
                                                    <li><a href="{{ route('consulta_procadm') }}">Consulta Processos Administrativos</a></li>

                                                    <li><a href="{{ route('consulta_conta_corrente') }}">Consulta Conta Corrente</a></li>

                                                    @if ( Auth::user()->hasRole('gbravo'))
                                                        <li><a href="{{ route('status_empresas') }}">Status por Empresa</a></li>
                                                    @endif
                                                    @if ( !Auth::user()->hasRole('gcliente'))
                                                        <li><a href="{{ route('about') }}">Performance</a></li>
                                                        <li><a href="{{ route('cargas_grafico') }}"> Status das Integrações</a></li>
                                                        <li><a href="{{ route('graficos') }}" target="_blank">Visão Geral</a></li>
                                                        <li><a href="{{ route('desempenho_entregas') }}" target="_blank">Desempenho das Entregas</a></li>
                                                    @endif
                                                    <li><a href="{{ route('arquivos.index') }}">Arquivos</a></li>
                                                </li>
                                        </ul>
                                    </div>
                                </div>

                            </li>
                    @endif

                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#tax-calendar2"><i class="fa fa-btn fa-calendar"></i>Paralegal</a>
                                    <div id="tax-calendar2" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <ul class="nav navbar-nav">
                                                <li class="panel panel-default" id="dropdown">
                                                    <ul class="nav navbar-nav">
                                                        <li><a href="{{ route('calendario') }}"><i class="fa fa-btn fa-calendar"></i>Calendário</a></li>
                                                    </ul>
                                                    <a data-toggle="collapse" href="#conta-corrente"><i class="fa fa-usd" aria-hidden="true"></i> Conta Corrente</a>
                                                    <div id="conta-corrente" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('movtocontacorrentes.create') }}"> Adicionar</a></li>
                                                            <li><a href="{{ route('movtocontacorrentes.search') }}"> Consultar</a></li>
                                                            <li><a href="{{ route('movtocontacorrentes.import') }}"> Importar</a></li>
                                                        </ul>
                                                    </div>
                                                    </div>
                                                </li>
                                                <li class="panel panel-default" id="dropdown">
                                                    <a data-toggle="collapse" href="#processos-administrativos"><i class="fa fa-inbox" aria-hidden="true"></i> Processos Administrativos</a>
                                                    <div id="processos-administrativos" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('processosadms.create') }}"> Adicionar</a></li>
                                                            <li><a href="{{ route('processosadms.search') }}"> Consultar</a></li>
                                                            <li><a href="{{ route('processosadms.import') }}"> Importar</a></li>
                                                        </ul>
                                                    </div>
                                                    </div>
                                                </li>
                                                
                                        </ul>
                                    </div>
                                </div>

                            </li>
                        @endif

                        @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('analyst'))
                        <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#integracoes"><i class="fa fa-exchange" aria-hidden="true"></i>Integrações</a>
                                    <div id="integracoes" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <ul class="nav navbar-nav">
                                                <li class="panel panel-default" id="dropdown">
                                                    <li><a href="{{ route('cargas') }}">Cargas</a></li>
                                                </li>
                                        </ul>
                                    </div>
                                </div>

                            </li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('gcliente'))
                    <li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#pagamentos"><i class="fa fa-money" aria-hidden="true"></i>Pagamentos</a>
                                <div id="pagamentos" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            <li class="panel panel-default" id="dropdown">
                                                <li><a href="{{ route('guiaicms.icms') }}">ICMS</a></li>
                                                @if ( Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                                                    <li><a href="{{ route('guiaicms.conferencia') }}">Conferência</a></li>
                                                @endif
                                                @if ( !Auth::user()->hasRole('gcliente'))
                                                <li><a href="{{ route('guiaicms.search_criticas') }}">Criticas</a></li>
                                                @endif
                                                <li><a href="{{ route('guiaicms.create') }}">Incluir</a></li>
                                                <li><a href="{{ route('guiaicms.listar') }}">Manipular</a></li>
                                                <li><a href="{{ route('codigosap.create') }}">Atualizar código SAP</a></li>
                                                <li><a href="{{ route('centrocustos.create') }}">Atualizar Centro de Custo</a></li>
                                            </li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endif

                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#workflow-manager"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Workflow Manager</a>
                                
                                <div id="workflow-manager" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            <li><a href="{{ route('aprovacao') }}"><i class="fa fa-btn fa-upload"></i>Aprovação</a></li>
                                            <li><a href="{{ route('entregas.index') }}"><i class="fa fa-btn fa-upload"></i>Entregas</a></li>
                                            <li><a href="{{ route('guiaicms.search_criticas_entrega') }}"><i class="fa fa-btn fa-trash"></i>Criticas Entrega</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif
                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#repository"><i class="fa fa-database" aria-hidden="true"></i> Repository</a>
                                <div id="repository" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            <li><a href="{{ route('arquivos.index') }}"><i class="fa fa-btn fa-upload"></i>Arquivos</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif
                        <?php } ?>

                        @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                        <li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#configuration"><i class="fa fa-cog" aria-hidden="true"></i> Configuration</a>
                                <div id="configuration" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                            <li><a href="{{ route('categorias.index') }}">Categorias Fiscais</a></li>
                                            <li><a href="{{ route('tributos.index') }}">Tributos</a></li>
                                            <li><a href="{{ route('regras.index') }}">Regras</a></li>

                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#regrasLote">Regras Envio por Lote</a>
                                                <div id="regrasLote" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('regraslotes.envio_lote') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('regraslotes.lote_consulta') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>
                                            
                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#usuarios">Usuários</a>
                                                <div id="usuarios" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('usuarios.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('usuarios.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#grupoEmpresas">Grupo de Empresas</a>
                                                <div id="grupoEmpresas" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('grupoempresas.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('grupoempresas') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>

                                            <li><a href="{{ route('atividades.index') }}">Atividades</a></li>
                                            <li><a href="{{ route('guiaicms.Job', 'getType=1') }}">Ler PDF</a></li>
                                            @endif
                                            <li><a href="{{ route('empresas.index') }}">Empresas</a></li>
                                            <?php if (!empty(session()->get('seid'))){ ?> 
                                                <li><a href="{{ route('estabelecimentos.index') }}">Estabelecimentos</a></li>
                                            <?php } ?>
                                            <li><a href="{{ route('municipios.index') }}">Municipios</a></li>
                                            <li><a href="{{ route('feriados') }}">Feriados</a></li>
                                            <li><a href="{{ route('mensageriaprocadms.create') }}">Mensageria Processo Administrativo</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                    <li class="panel panel-default" id="dropdown">
                    <a data-toggle="collapse" href="#cronograma"><i class="fa fa-clock-o"></i>Cronograma</a>
                        <div id="cronograma" class="panel-collapse collapse">
                            <div class="panel-body">
                            <ul class="nav navbar-nav">
                                <li class="panel panel-default" id="dropdown">
                                 <li class="panel panel-default" id="dropdown">
                                    <a data-toggle="collapse" href="#analistas">Analistas</a>
                                    <div id="analistas" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <ul class="nav navbar-nav">
                                                <li><a href="{{ route('atividadesanalista.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                <li><a href="{{ route('atividadesanalista.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                                <li><a href="{{ route('cronogramaatividades.create') }}">Gerar</a></li>
                                <li><a href="{{ route('cronogramaatividades.index') }}">Manipular</a></li>
                                <li><a href="{{ route('cronogramaatividades.GerarConsulta') }}">Consulta</a></li>
                                <li><a href="{{ route('cronogramaatividades.Gerarmensal') }}">Mensal</a></li>
                                <li><a href="{{ route('cronogramaatividades.Gerarsemanal') }}">Semanal</a></li>
                                <li><a href="{{ route('cronogramaatividades.GerarchecklistCron') }}">Checklist</a></li>
                                </li>
                            </ul>
                            </div>
                        </div>
                    </li>
                    @endif 
                @endif 
            </ul>
        </div><!-- /.navbar-collapse -->
    </nav>
</div>


<div class="top-header">
    <div class="menu">
        <button type="button" class="navbar-toggle" id="sidebarCollapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>
    @if (!Auth::guest())
    <div class="dropdown navbar-right">
        <a id="dLabel" data-target="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <img src="{{ URL::to('/') }}/assets/img/menu.svg">
        </a>
        <ul class="dropdown-menu" aria-labelledby="dLabel">
        <li>
                <a href="{{ url('/logout') }}">
                    <img style="height:16px;padding-bottom: 2px" src="{{ URL::to('/') }}/assets/img/{{ Auth::user()->roles()->first()->name }}-icon.png" title="{{ Auth::user()->roles()->first()->display_name }}" /> ({{ Auth::user()->name.' ' }})
                    <br>
                    <i class="fa fa-btn fa-sign-out"></i>Logout
                </a>
            </li>
        </ul>    
    </div>
    @endif           
  </div>





 <script type="text/javascript">
        
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                 $('#sidebar').toggleClass('active');
                 $('#sidebarCollapse').toggleClass('auto-left');
                 $('#content').toggleClass('auto-left');
            });
        });

        $(function () {
            $('.navbar-toggle').click(function () {
                $('.navbar-nav').toggleClass('slide-in');
                $('.side-body').toggleClass('body-slide-in');
                $('#search').removeClass('in').addClass('collapse').slideUp(200);
            });

        $('#search-trigger').click(function () {
                $('.navbar-nav').removeClass('slide-in');
                $('.side-body').removeClass('body-slide-in');
            });
        });

        $(function() {
            $('#main-menu').smartmenus({
                subMenusSubOffsetX: 1,
                subMenusSubOffsetY: -8
            });
        });
</script> 
