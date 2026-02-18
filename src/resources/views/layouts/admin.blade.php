<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>kintai</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}?v={{ time() }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a href="{{ url('/') }}">
                <img src="{{ asset('/img/COACHTECHヘッダーロゴ.png') }}" class="header__logo">
            </a>

            <nav class="header-nav">
                @auth
                    @if (request()->is('attendance') && isset($state) && $state === 'finished')
                        {{-- 退勤後（お疲れ様でした が出ている時） --}}
                        <a href="/attendance/list" class="header-nav__link">今月の出勤一覧</a>
                        <a href="/stamp_correction_request/list" class="header-nav__link">申請一覧</a>
                    @else
                        {{-- 通常時 --}}
                        <a href="/attendance" class="header-nav__link">勤怠</a>
                        <a href="/attendance/list" class="header-nav__link">勤怠一覧</a>
                        <a href="/stamp_correction_request/list" class="header-nav__link">申請</a>
                    @endif

                    <form action="/logout" class="logout-form" method="post">
                        @csrf
                        <button class="header-nav__link">ログアウト</button>
                    </form>
                @endauth
            </nav>


        </div>
    </header>

    <main>
        @yield('content')
    </main>

</body>

</html>
