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

        @yield('content')
    </div>
</body>
</html>