<!doctype html>
<html lang="en">
@include('layouts.head')
<body>

@include('layouts.nav')

<main>
    <div class="container">
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
                  position:absolute; right: 50px; top:100px;
                  background: url("{{ URL::to('/') }}/assets/logo/logo-{{ \Illuminate\Support\Facades\Crypt::decrypt(session('seid')) }}.png") center / contain no-repeat;
                  width: 100px;
                }
            </style>
            <div class="navbar-brand"></div>
        @endif

        @yield('content')
    </div>
</main>
</body>
</html>