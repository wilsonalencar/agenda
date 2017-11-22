<!doctype html>
<html lang="pt-br">
@include('layouts.head')
<body>

@include('layouts.nav')


    <div id="content">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        @if (session('seid') && !Auth::guest())
            <style>
                .navbar-brand {
                  padding: 0px; /* firefox bug fix */
                }
                .navbar-brand {
                    position: absolute;
                    right: 55px;
                    top: 10px;
                    width: 32px;
                    height: 32px;
                    background: url("{{ URL::to('/') }}/assets/logo/logo-{{ session('seid') }}.png") center / contain no-repeat;
                }
            </style>
            <div class="navbar-brand"></div>
        @endif

        @yield('content')
    </div>
</body>
</html>