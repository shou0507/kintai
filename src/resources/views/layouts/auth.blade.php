<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>kintai</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
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
                    <a href="/admin/attendance/list" class="header-nav__link">
                        勤怠一覧
                    </a>
                    <a href="/admin/staff/list" class="header-nav__link">
                        スタッフ一覧
                    </a>
                    <a href="{{ route('admin.stamp_correction_request.list') }}" class="header-nav__link">
                        申請一覧
                    </a>

                    <form action="/admin/logout" class="logout-form" method="post">
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
