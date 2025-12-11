<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            <div class="header__search">
                <form action="{{ route('home') }}" method="GET">
                    <input type="text" name="keyword" placeholder="なにをお探しですか？" value="{{ request('keyword') }}">
                    @if(request('tab'))
                        <input type="hidden" name="tab" value="{{ request('tab') }}">
                    @endif
                </form>
            </div>
            <nav class="header__nav">
                <ul class="nav__list">
                    <li class="nav__item">
                        @auth
                            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                                @csrf
                                <button type="submit" class="nav__text">ログアウト</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="nav__text">ログイン</a>
                        @endauth
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('mypage') }}" class="nav__text">マイページ</a>
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('items.create') }}" class="nav__button">出品</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!-- フラッシュメッセージ -->
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@yield('content')
</body>
</html>