@extends('layouts.auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="art-page">
        <div class="art-title-row">
            <div class="art-title-bar"></div>
            {{-- その日の日付 --}}
            <h1 class="art-title">
                {{ $date->format('Y年n月j日') }}の勤怠
            </h1>
        </div>

        {{-- ✅ 月バー→ 日付切替（前日/翌日） --}}
        <div class="att-monthbar">
            <a href="{{ url('/admin/attendance/list') }}?date={{ $prevDate }}" class="art-monthbtn">
                <img src="{{ asset('img/088deff71873c09816bca59dd0d7efa7308e8fba (1).png') }}" alt="前日"
                    class="art-arrow-img">
                <span class="art-monthbtn-text">前日</span>
            </a>

            {{-- 中央（月表示 + カレンダー画像） --}}
            <div class="art-monthcenter">
                <img src="{{ asset('img/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}" alt="カレンダー"
                    class="art-calendar-img">
                {{-- ✅ 中央：今日の日付 --}}
                <span class="art-monthlabel">{{ $date->format('Y/m/d') }}</span>
            </div>

            <a href="{{ url('/admin/attendance/list') }}?date={{ $nextDate }}" class="art-monthbtn">
                <span class="art-monthbtn-text">翌日</span>
                <img src="{{ asset('img/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}" alt="翌日"
                    class="art-arrow-img arrow-next">
            </a>
        </div>

        <div class="art-tablewrap">
            <table class="art-table">
                <thead>
                    <tr class="art-head">
                        <th class="th-name">名前</th>
                        <th class="th-in">出勤</th>
                        <th class="th-out">退勤</th>
                        <th class="th-break">休憩</th>
                        <th class="th-total">合計</th>
                        <th class="th-detail">詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        // 分→ "H:MM"
                        $fmtHM = function (?int $minutes): string {
                            if ($minutes === null) {
                                return '';
                            }
                            $minutes = max(0, $minutes);
                            $h = intdiv($minutes, 60);
                            $m = $minutes % 60;
                            return $h . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                        };
                    @endphp

                    @foreach ($attendances as $rec)
                        @php
                            $user = $rec->user;

                            $in = $rec->clock_in ? \Carbon\Carbon::parse($rec->clock_in)->format('H:i') : '';

                            $out = $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('H:i') : '';

                            $break = $fmtHM($rec->break_minutes);
                            $total = $fmtHM($rec->work_minutes);
                        @endphp


                        <tr>
                            <td class="col-name">{{ $user->name }}</td>
                            <td class="col-in">{{ $in }}</td>
                            <td class="col-out">{{ $out }}</td>
                            <td class="col-break">{{ $break }}</td>
                            <td class="col-detail">{{ $total }}</td>
                            <td class="col-detail">
                                <a href="{{ url('/admin/attendance/' . $rec->id) }}" class="art-detaillink">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
