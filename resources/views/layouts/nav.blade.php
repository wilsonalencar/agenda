<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <div style="display:inline; position:relative; top:5px; font-family:'Arial', 'Helvetica', sans-serif; font-size:21px;"><span><font color="#B22222"><b>B</b></font>ravo - Tax Calendar</span> <!--- <img style="height:20px; margin-bottom: 5px" src="{{ URL::to('/') }}/assets/img/v_logo_small.png" /><span>agenda</span>--></div>
    </div>
     <!-- Right Side Of Navbar -->
    <ul id="main-menu" class="sm sm-clean navbar-right">
        @if (Auth::guest())
        <li><a href="{{ url('/login') }}"><i class="fa fa-btn fa-sign-in"></i> Login</a></li>
        <li><a href="{{ url('/register') }}"><i class="fa fa-btn fa-user"></i> Cadastre-se</a></li>
        @else
        <li><a href="{{ route('home') }}"><i class="fa fa-btn fa-home"></i></a></li>
        <li><a href="#">|</a></li>
        <li><a href="{{ route('about') }}"><i class="fa fa-btn fa-info"></i> Info</a></li>
        <li><a href="#">|</a></li>
            @if ( Auth::user()->hasRole('user') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor'))
                <li><a href="{{ route('calendario') }}"><i class="fa fa-btn fa-calendar"></i> Calendário</a></li>
                <li><a href="#">|</a></li>
            @endif
            @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                 <li><a href="#"><i class="fa fa-btn fa-table"></i>Integração</a>
                        <ul>
                            <li></li>
                            <li><a href="{{ route('cargas') }}"><i class="fa fa-btn fa-file-text-o"></i> Por Estabelecimento</a></li>
                            <li><a href="{{ route('cargas_grafico') }}"><i class="fa fa-btn fa-file-text-o"></i> Visualização Grafica</a></li>
                        </ul>
                 </li>
                 <li><a href="#">|</a></li>
            @endif


            @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                 <li><a href="#"><i class="fa fa-btn fa-table"></i>Tax Calendar</a>
                        <ul>
                            <li></li>
                            <li><a href="{{ route('movtocontacorrente') }}"><i class="fa fa-btn fa-file-text-o"></i>Conta Corrente</a></li>
                            <li><a href="{{ route('processosadms.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Processos Administrativos</a></li>
                        </ul>
                 </li>
                 <li><a href="#">|</a></li>
            @endif


            @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                <li><a href="{{ route('entregas.index') }}"><i class="fa fa-btn fa-upload"></i> Entrega</a></li>
                <li><a href="#">|</a></li>
            @endif
            @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                <li><a href="{{ route('arquivos.index') }}"><i class="fa fa-btn fa-paperclip"></i> Arquivo</a></li>
                <li><a href="#">|</a></li>
            @endif
            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                <li><a href="#"><i class="fa fa-btn fa-table"></i> Configurações</a>
                    <ul>
                        <li></li>
                        @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                        <li><a href="{{ route('categorias.index') }}">Categorias Fiscais</a></li>
                        <li><a href="{{ route('tributos.index') }}">Tributos</a></li>
                        <li><a href="{{ route('regras.index') }}">Regras</a></li>
                        <li><a href="{{ route('usuarios.index') }}">Usuários</a></li>
                        <li><a href="{{ route('atividades.index') }}">Atividades</a></li>
                        <li><a href="{{ route('mensageriaprocadms.create') }}">Mensageria</a></li>
                        @endif
                        <li><a href="{{ route('empresas.index') }}">Empresas</a></li>
                        <li><a href="{{ route('estabelecimentos.index') }}">Estabelecimentos</a></li>
                        <li><a href="{{ route('municipios.index') }}">Municipios</a></li>
                        <li><a href="{{ route('feriados') }}">Feriados</a></li>
                    </ul>
                </li>
                <li><a href="#">|</a></li>
            @endif
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