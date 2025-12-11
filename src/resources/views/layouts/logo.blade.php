<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Coachtechフリマ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }} ">
    <link rel="stylesheet" href="{{ asset('css/common.css') }} ">
    @yield('css')
</head>
<body>
<header class="header">
    <div class="header__container">
        <div class="header-utilities">
            <div class="header__logo">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('/img/logo.svg') }}" alt="coachtechロゴ">
                </a>
            </div>
            <div class="header__search"></div>
            <nav class="header__nav"></nav>
        </div>
    </div>
</header>
@yield('content')
</body>
</html>