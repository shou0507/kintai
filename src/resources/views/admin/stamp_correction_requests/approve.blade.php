@extends('layouts.auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}?v={{ time() }}">
@endsection

@section('content')
    <div class="art-page">

        <div class="art-title-row">
            <div class="art-title-bar"></div>
            <h1 class="art-title">勤怠詳細</h1>
        </div>

        <div class="ad-card">
            @php
                $attendance = $scr->attendance;
                $date = $scr->date;

                // 申請値を優先（無ければ元勤怠）
                $clockInRaw = $scr->clock_in ?? $attendance->clock_in;
                $clockOutRaw = $scr->clock_out ?? $attendance->clock_out;

                // ※ DBが "HH:MM" なら parse しなくてもOK。念のため整形したいならこう:
                $clockIn = $clockInRaw ? \Carbon\Carbon::parse($clockInRaw)->format('H:i') : '';
                $clockOut = $clockOutRaw ? \Carbon\Carbon::parse($clockOutRaw)->format('H:i') : '';

                // 休憩は attendance_corrections 側を使う（あなたのテーブル設計）
                $b1in = $scr->break1_in ?? '';
                $b1out = $scr->break1_out ?? '';
                $b2in = $scr->break2_in ?? '';
                $b2out = $scr->break2_out ?? '';

                // 備考も申請値を優先
                $note = $scr->note ?? ($attendance->note ?? '');
            @endphp


            {{-- 名前 --}}
            <div class="ad-row">
                <div class="ad-label">名前</div>
                <div class="ad-value">{{ $scr->user->name ?? '' }}</div>
            </div>
            <div class="ad-line"></div>

            {{-- 日付 --}}
            <div class="ad-row ad-row-date">
                <div class="ad-label">日付</div>
                <div class="ad-value">
                    <div class="ad-date-wrap">
                        <div class="ad-date-year">{{ \Carbon\Carbon::parse($date)->format('Y年') }}</div>
                        <div class="ad-date-md">{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</div>
                    </div>
                </div>
            </div>
            <div class="ad-line"></div>

            {{-- 出勤・退勤 --}}
            <div class="ad-row">
                <div class="ad-label">出勤・退勤</div>
                <div class="ad-value">
                    <div class="ad-timepair">
                        <input class="ad-timebox" value="{{ $clockIn }}" disabled>
                        <span class="ad-tilde">〜</span>
                        <input class="ad-timebox" value="{{ $clockOut }}" disabled>
                    </div>
                </div>
            </div>
            <div class="ad-line"></div>

            {{-- 休憩 --}}
            <div class="ad-row">
                <div class="ad-label">休憩</div>
                <div class="ad-value">
                    <div class="ad-timepair">
                        <input class="ad-timebox" value="{{ $b1in }}" disabled>
                        <span class="ad-tilde">〜</span>
                        <input class="ad-timebox" value="{{ $b1out }}" disabled>
                    </div>
                </div>
            </div>
            <div class="ad-line"></div>

            {{-- 休憩2（無ければ空白） --}}
            <div class="ad-row">
                <div class="ad-label">休憩2</div>
                <div class="ad-value">
                    <div class="ad-timepair">
                        <input class="ad-timebox" value="{{ $b2in }}" disabled>
                        <span class="ad-tilde">〜</span>
                        <input class="ad-timebox" value="{{ $b2out }}" disabled>
                    </div>
                </div>
            </div>
            <div class="ad-line"></div>

            {{-- 備考 --}}
            <div class="ad-row ad-row-note">
                <div class="ad-label">備考</div>
                <div class="ad-value">
                    <textarea class="ad-note" rows="3" disabled>{{ $scr->note ?? ($attendance->note ?? '') }}</textarea>
                </div>
            </div>

            {{-- 承認ボタン --}}
            <form method="POST" action="{{ route('admin.stamp_correction_request.approve.update', ['id' => $scr->id]) }}"
                id="approveForm">
                @csrf
                <div class="ad-actions">
                    @if ($scr->status === 'pending')
                        <button type="submit" class="ad-editbtn" id="approveBtn">承認</button>
                    @else
                        <p class="ad-approved">承認済み</p>
                    @endif
                </div>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('approveForm');
            const btn = document.getElementById('approveBtn');
            if (!form || !btn) return;

            form.addEventListener('submit', () => {
                btn.disabled = true;
                btn.textContent = '処理中...';
            });
        });
    </script>
@endsection
