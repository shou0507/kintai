@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}?v={{ time() }} ">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-status">
        @if ($state === 'off')
        勤務外
        @elseif ($state === 'working')
        出勤中
        @elseif ($state === 'break')
        休憩中
        @elseif ($state === 'finished')
        退勤済
        @endif
    </div>

    <div class="attendance-date">
        {{ now()->isoFormat('Y年M月D日（ddd）') }}
    </div>

    <div class="attendance-time" id="current-time">
        {{ now()->format('H:i') }}
    </div>

    @if ($state === 'finished')
        <div class="attendance-message" id="attendance-message">
        お疲れ様でした。
        </div>
    @endif

    <form action="/attendance" method="post">
        @csrf

        {{-- 勤務外：出勤だけ --}}
        @if($state === 'off')
            <button class="attendance-button clock-in" type="submit" name="action" value="clock_in">
            出勤
            </button>
        @endif

        {{-- 勤務中：退勤 + 休憩入 --}}
        @if($state === 'working')
            <button class="attendance-button clock-out" type="submit" name="action" value="clock_out">
                退勤
            </button>

            <button class="attendance-button break-in" type="submit" name="action" value="break_in">
                休憩入
            </button>
         @endif

         {{-- 休憩中：休憩戻 --}}
        @if($state === 'break')
        <button class="attendance-button break-back" type="submit" name="action" value="break_back">
            休憩戻
        </button>
        @endif
    </form>
</div>

<script>
    setInterval(() => {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('current-time').textContent = `${h}:${m}`;
    }, 1000);
</script>
@endsection