@extends('layouts.admin')

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
                $locked = !empty($pendingCorrection);

                $breaks = $attendance->breaks ?? collect();
                $b1 = $breaks->get(0);
                $b2 = $breaks->get(1);

                // 初期表示用（HH:MM）
                $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
                $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';
                $b1in = $b1 && $b1->break_in ? \Carbon\Carbon::parse($b1->break_in)->format('H:i') : '';
                $b1out = $b1 && $b1->break_out ? \Carbon\Carbon::parse($b1->break_out)->format('H:i') : '';
                $b2in = $b2 && $b2->break_in ? \Carbon\Carbon::parse($b2->break_in)->format('H:i') : '';
                $b2out = $b2 && $b2->break_out ? \Carbon\Carbon::parse($b2->break_out)->format('H:i') : '';
            @endphp

            <form method="POST" action="/attendance/detail/{{ $attendance->id }}" id="attendanceForm"
                class="{{ $locked ? 'is-locked' : '' }}">
                @csrf

                {{-- 名前 --}}
                <div class="ad-row">
                    <div class="ad-label">名前</div>
                    <div class="ad-value">{{ Auth::user()->name ?? '' }}</div>
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

                {{-- 出勤・退勤（入力） --}}
                <div class="ad-row">
                    <div class="ad-label">出勤・退勤</div>
                    <div class="ad-value">
                        <div class="ad-timepair">
                            <input class="ad-timebox" name="clock_in" value="{{ $clockIn }}"
                                {{ $locked ? 'disabled' : '' }}>
                            <span class="ad-tilde">〜</span>
                            <input class="ad-timebox" name="clock_out" value="{{ $clockOut }}"
                                {{ $locked ? 'disabled' : '' }}>
                        </div>

                        @error('clock_in')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror
                        @error('clock_out')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>
                </div>
                <div class="ad-line"></div>

                {{-- 休憩（入力） --}}
                <div class="ad-row">
                    <div class="ad-label">休憩</div>
                    <div class="ad-value">
                        <div class="ad-timepair">
                            <input class="ad-timebox" name="break1_in" value="{{ $b1in }}"
                                {{ $locked ? 'disabled' : '' }}>
                            <span class="ad-tilde">〜</span>
                            <input class="ad-timebox" name="break1_out" value="{{ $b1out }}"
                                {{ $locked ? 'disabled' : '' }}>
                        </div>

                        @error('break1_in')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror
                        @error('break1_out')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>
                </div>
                <div class="ad-line"></div>

                {{-- 休憩2（入力） --}}
                <div class="ad-row">
                    <div class="ad-label">休憩2</div>
                    <div class="ad-value">
                        <div class="ad-timepair">
                            <input class="ad-timebox" name="break2_in" value="{{ $b2in }}"
                                {{ $locked ? 'disabled' : '' }}>
                            <span class="ad-tilde">〜</span>
                            <input class="ad-timebox" name="break2_out" value="{{ $b2out }}"
                                {{ $locked ? 'disabled' : '' }}>
                        </div>

                        @error('break2_in')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror
                        @error('break2_out')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                <div class="ad-line"></div>

                {{-- 備考（入力） --}}
                <div class="ad-row ad-row-note">
                    <div class="ad-label">備考</div>
                    <div class="ad-value">
                        <textarea class="ad-note" name="note" rows="3" {{ $locked ? 'disabled' : '' }}>{{ $attendance->note ?? '' }}
                            </textarea>

                        @error('note')
                            <div class="form__error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                {{-- ボタン --}}
                <div class="ad-actions">
                    @if ($locked)
                        <p class="ad-pending-message">
                            ※承認待ちのため修正はできません。
                        </p>
                    @else
                        <button type="submit" class="ad-editbtn">修正</button>
                    @endif
                </div>

            </form>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('attendanceForm');
            if (!form) return;

            form.addEventListener('submit', () => {
                const btn = form.querySelector('.ad-editbtn');
                if (btn) {
                    btn.textContent = '承認待ち';
                    btn.disabled = true;
                }
            });
        });
    </script>
@endsection
