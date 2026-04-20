<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mock-project_FleaMarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    @php
        $hidePages = ['login', 'register', 'admin.login'];
        $hideLogin = 'stamp_correction_request.list';
    @endphp
    <header class="header">
        <div class="header__inner">
            <img class="header__img" src="{{ asset('images/logo.png') }}" alt="logo" >
            @if (!Route::is($hidePages))

                {{-- 管理者ログイン --}}
                @auth('admin')
                    <div class="header__nav">
                        <a class="button__nav" href="{{ route('admin.index') }}">勤怠一覧</a>
                        <a class="button__nav" href="{{ route('admin.staff_list') }}">スタッフ一覧</a>
                        <a class="button__nav" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                        <form action="/admin/logout" method="post">
                            @csrf
                            <button class="button__logout">ログアウト</button>
                        </form>
                    </div>
                @endauth

                {{-- 一般ユーザー --}}
                @auth('web')
                    <div class="header__nav">
                        <a class="button__nav" href="{{ route('attendance.index') }}">勤怠</a>
                        <a class="button__nav" href="{{ route('attendance.list') }}">勤怠一覧</a>
                        <a class="button__nav" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>
                        <form action="/logout" method="post">
                            @csrf
                            <button class="button__logout">ログアウト</button>
                        </form>
                    </div>
                @endauth

                @if(!Route::is($hideLogin))
                    {{-- 未ログイン --}}
                    @guest
                        <div class="header__nav">
                            <a class="button__login" href="/login">ログイン</a>
                        </div>
                    @endguest
                @endif

            @endif
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>