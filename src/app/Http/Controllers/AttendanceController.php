<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        /**
         * 出勤登録画面表示
         */
        $today = Carbon::today()->toDateString();

        // 今日の勤怠を取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // 休憩中判定：未終了の休憩があるか
        $isOnBreak = false;
        if ($attendance) {
            $isOnBreak = AttendanceBreak::where('attendance_id', $attendance->id)
                ->whereNull('break_out')
                ->exists();
        }

        /**
         * 状態判定
         * off      : レコードなし or clock_inなし
         * working  : clock_inあり & clock_outなし & 休憩中ではない
         * break    : break_inあり & break_outなし
         * finished : clock_outあり
         */
        if (! $attendance || ! $attendance->clock_in) {
            $state = 'off';
        } elseif ($attendance->clock_out) {
            $state = 'finished';
        } elseif ($isOnBreak) {
            $state = 'break';
        } else {
            $state = 'working';
        }

        return view('attendance.index', compact('state', 'attendance'));
    }

    /**
     * 出勤・退勤処理
     */
    public function store(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $action = $request->input('action'); // clock_in / clock_out / break_in / break_back

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'date' => $today,
            ],
            [
                'clock_in' => null,
                'clock_out' => null,
                'note' => null,
            ]
        );

        $openBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_out')
            ->latest('break_in')
            ->first();

        // 出勤
        if ($action === 'clock_in' && ! $attendance->clock_in) {
            $attendance->clock_in = Carbon::now();
            $attendance->save();

            return redirect('/attendance');
        }

        // 休憩入
        if ($action === 'break_in') {
            if ($attendance->clock_in && ! $attendance->clock_out && ! $openBreak) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_in' => Carbon::now(),
                    'break_out' => null,
                ]);
            }

            return redirect('/attendance');
        }

        // 休憩戻
        if ($action === 'break_back') {
            if ($openBreak) {
                $openBreak->break_out = Carbon::now();
                $openBreak->save();
            }

            return redirect('/attendance');
        }

        // 退勤
        if ($action === 'clock_out') {
            if ($attendance->clock_in && ! $attendance->clock_out && ! $openBreak) {
                $attendance->clock_out = Carbon::now();
                $attendance->save();
            }

            return redirect('/attendance')->with('after_clock_out', true);
        }

        return redirect('/attendance');
    }

    public function show(Request $request)
    {
        $month = $request->query('month');

        $base = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        // 今月の日付（1日〜月末）を必ず全て出す
        $days = CarbonPeriod::create($start, $end);

        // 自分の勤怠だけを今月分取得（休憩も一緒に）
        $records = Attendance::with('breaks')
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        return view('attendance.list', [
            'days' => $days,
            'records' => $records,
            'currentMonthLabel' => $base->format('Y/m'),
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function detail(Request $request, $id)
    {
        // ① idがある日（通常）
        if ((int) $id !== 0) {
            $attendance = Attendance::with('breaks')
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $pendingCorrection = AttendanceCorrection::where('user_id', Auth::id())
                ->where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            return view('attendance.detail', [
                'attendance' => $attendance,
                'date' => $attendance->date->format('Y-m-d'),
                'pendingCorrection' => $pendingCorrection,
            ]);
        }

        // ② 勤怠が無い日（id=0で来る想定）
        $dateStr = $request->query('date');
        if (! $dateStr) {
            abort(404);
        }

        $date = Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay();

        // 既にその日付のレコードがあればそれを表示、無ければ「空の詳細」を表示
        $attendance = Attendance::with('breaks')
            ->where('user_id', Auth::id())
            ->whereDate('date', $date->toDateString())
            ->first();

        // 無ければ “未保存の空モデル” を渡す（画面は出せる）
        if (! $attendance) {
            $attendance = new Attendance([
                'user_id' => Auth::id(),
                'date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'note' => null,
            ]);
            $attendance->setRelation('breaks', collect());
        }

        return view('attendance.detail', [
            'attendance' => $attendance,
            'date' => $date->format('Y-m-d'),
        ]);
    }

    public function storeCorrection(AttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')
            ->where('id', $id)
            ->where('user_id', Auth::id())   // ✅ user-id → user_id
            ->firstOrFail();

        $exists = AttendanceCorrection::where('user_id', Auth::id()) // ✅ user-id → user_id
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        // 空文字を null に寄せる（nullable を効かせるため）
        $request->merge([
            'clock_in' => $request->input('clock_in') ?: null,
            'clock_out' => $request->input('clock_out') ?: null,
            'break1_in' => $request->input('break1_in') ?: null,
            'break1_out' => $request->input('break1_out') ?: null,
            'break2_in' => $request->input('break2_in') ?: null,
            'break2_out' => $request->input('break2_out') ?: null,
            'note' => $request->input('note') ?: null,
        ]);

        // ✅ ここで「承認待ち」申請を保存する
        AttendanceCorrection::create([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'date' => $attendance->date->format('Y-m-d'),

            'clock_in' => $data['clock_in'] ?? null,
            'clock_out' => $data['clock_out'] ?? null,
            'break1_in' => $data['break1_in'] ?? null,
            'break1_out' => $data['break1_out'] ?? null,
            'break2_in' => $data['break2_in'] ?? null,
            'break2_out' => $data['break2_out'] ?? null,
            'note' => $data['note'] ?? null,

            'status' => 'pending',
        ]);

        return redirect()->to("/attendance/detail/{$id}");
    }
}
