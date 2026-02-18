@extends('layouts.auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/staff-list.css') }}?v={{ time() }}">
@endsection


@section('content')
<div class="art-page">
    <div class="art-title-row">
        <div class="art-title-bar"></div>
        <h1 class="art-title">スタッフ一覧</h1>
    </div>

    <div class="staff-wrap">
        <table class="staff-table">
            <thead>
                <tr>
                    <th class="th-name">名前</th>
                    <th class="th-email">メールアドレス</th>
                    <th class="th-monthly">月次勤怠</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="td-name">{{ $user->name }}</td>
                        <td class="td-email">{{ $user->email }}</td>
                        <td class="td-monthly">
                            <a href="{{ url('/admin/attendance/staff/'.$user->id) }}" class="monthly-link">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection