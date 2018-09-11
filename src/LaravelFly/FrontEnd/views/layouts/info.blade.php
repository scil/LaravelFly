<!DOCTYPE html>
<html>
<head>
    <link href="https://ajax.aspnetcdn.com/ajax/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <ul class="nav nav-tabs">
                @foreach($INFO_ITEMS as $li)
                    <li role="presentation"><a href="/{!! $LARAVEL_FLY_PREFIX.'/info/'.$li !!}">{!! $li !!}</a></li>
                @endforeach
            </ul>

        </div>
    </div>
    <main class="row">
        <div class="col-sm-10 col-sm-offset-2">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
