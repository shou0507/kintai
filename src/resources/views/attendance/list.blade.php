@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="art-page">
        <div class="art-title-row">
            <div class="art-title-bar"></div>
            <h1 class="art-title">勤怠一覧</h1>
        </div>

        <div class="att-monthbar">
            {{-- 前月 --}}
            <a href="{{ url('/attendance/list') }}?month={{ $prevMonth }}" class="art-monthbtn">
                <img src="{{ asset('img/088deff71873c09816bca59dd0d7efa7308e8fba (1).png') }}" alt="前月"
                    class="art-arrow-img">
                <span class="art-monthbtn-text">前月</span>
            </a>

            {{-- 中央（月表示 + カレンダー画像） --}}
            <div class="art-monthcenter">
                <img src="{{ asset('img/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}" alt="カレンダー"
                    class="art-calendar-img">
                <span class="art-monthlabel">{{ $currentMonthLabel }}</span>
            </div>

            {{-- 翌月 --}}
            <a href="{{ url('/attendance/list') }}?month={{ $nextMonth }}" class="art-monthbtn">
                <span class="art-monthbtn-text">翌月</span>
                <img src="{{ asset('img/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}" alt="翌月"
                    class="art-arrow-img arrow-next">
            </a>
        </div>

        {{-- 一覧枠 --}}
        <div class="art-tablewrap">
            <table class="art-table">
                <thead>
                    <tr class="art-head">
                        <th class="th-date">日付</th>
                        <th class="th-in">出勤</th>
                        <th class="th-out">退勤</th>
                        <th class="th-break">休憩</th>
                        <th class="th-total">合計</th>
                        <th class="th-detail">詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        // 分→ "H:MM"（例: 1:00 / 0:05）
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

                    @foreach ($days as $day)
                        @php
                            $key = $day->format('Y-m-d');
                            $rec = $records[$key] ?? null;

                            // 出勤/退勤は 09:00 形式なら H:i、9:00 にしたいなら G:i
                            $in = $rec && $rec->clock_in ? $rec->clock_in->format('H:i') : '';
                            $out = $rec && $rec->clock_out ? $rec->clock_out->format('H:i') : '';

                            // break_minutes / work_minutes は Attendanceアクセサ想定
                            $break = $rec && $rec->clock_in ? $fmtHM($rec->break_minutes) : '';
                            $total = $rec && $rec->clock_in ? $fmtHM($rec->work_minutes) : '';

                            $dow = ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek];
                        @endphp

                        <tr>
                            <td class="col-date">{{ $day->format('m/d') }}({{ $dow }})</td>
                            <td class="col-in">{{ $in }}</td>
                            <td class="col-out">{{ $out }}</td>
                            <td class="col-break">{{ $break }}</td>
                            <td class="col-total">{{ $total }}</td>
                            <td class="col-detail">
                                @php
                                    $detailUrl =
                                        $rec && $rec->id
                                            ? url('/attendance/detail/' . $rec->id)
                                            : url('/attendance/detail/0?date=' . $key);
                                @endphp

                                <a href="{{ $detailUrl }}" class="art-detaillink">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
