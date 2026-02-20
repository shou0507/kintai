<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay()
            : now()->startOfDay();

        // その日の勤怠
        $attendances = Attendance::with('user')
            ->whereDate('date', $date->toDateString())
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.list', [
            'date' => $date,
            'prevDate' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $date->copy()->addDay()->format('Y-m-d'),
            'attendances' => $attendances,
        ]);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

        $user = $attendance->user;

        return view('admin.attendance.detail', [
            'attendance' => $attendance,
            'user' => $user,
        ]);
    }

    public function update(AdminAttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);
        $data = $request->validated();

        $dateStr = Carbon::parse($attendance->date)->format('Y-m-d');

        // 勤怠（dateTime保存）
        $attendance->clock_in = Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$data['clock_in']}");
        $attendance->clock_out = Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$data['clock_out']}");
        $attendance->note = $data['note'];
        $attendance->save();

        // 既存休憩を取得（古い順＝休憩1,2 の扱いにする）
        $breaks = $attendance->breaks()->orderBy('break_in')->get()->values();

        // 休憩1
        $this->upsertBreak($attendance->id, $breaks->get(0), $data['break1_in'] ?? null, $data['break1_out'] ?? null, $dateStr);

        // 休憩2
        $this->upsertBreak($attendance->id, $breaks->get(1), $data['break2_in'] ?? null, $data['break2_out'] ?? null, $dateStr);

        return redirect('/admin/attendance/'.$attendance->id);
    }

    private function upsertBreak(int $attendanceId, ?AttendanceBreak $break, ?string $in, ?string $out, string $dateStr): void
    {
        // 両方空なら：既存があれば削除（仕様次第。削除したくないなら return に）
        if (! $in && ! $out) {
            if ($break) {
                $break->delete();
            }

            return;
        }

        $break = $break ?: new AttendanceBreak(['attendance_id' => $attendanceId]);

        $break->break_in = $in
            ? Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$in}")
            : $break->break_in; // break_in必須カラムなので、既存が無いのにnullは避ける

        // ※ break_in が必須なので「in が空で out だけ入力」されたら破綻する
        // そういう入力は Request 側で弾くのがベスト（後述）

        $break->break_out = $out
            ? Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$out}")
            : null;

        $break->save();
    }

    public function staffIndex(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // ✅ 勤怠が無い日の「詳細」から来た場合（date付き）
        if ($request->filled('date')) {
            $day = Carbon::createFromFormat('Y-m-d', $request->query('date'))->toDateString();

            // その日の勤怠を探す
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $day)
                ->first();

            // 無ければ作る（※新規ルート不要にするため、ここで作ってしまう）
            if (! $attendance) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $day,
                    // 必要なら初期値（clock_in/out は null でOK）
                ]);
            }

            // 既存の管理者詳細ルートへ（/admin/attendance/{id}）
            return redirect('/admin/attendance/'.$attendance->id);
        }

        // 「月次一覧の表示」
        $monthStr = $request->query('month');
        $base = $monthStr
            ? Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth()
            : now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        $days = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days->push($d->copy());
        }

        $records = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->date)->format('Y-m-d'));

        $prevMonth = $base->copy()->subMonth()->format('Y-m');
        $nextMonth = $base->copy()->addMonth()->format('Y-m');
        $currentMonthLabel = $base->format('Y年n月');

        return view('admin.attendance.staff_attendance.index', compact(
            'user', 'days', 'records', 'prevMonth', 'nextMonth', 'currentMonthLabel'
        ));
    }

    public function staffCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $monthStr = $request->query('month');
        $base = $monthStr
            ? Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth()
            : now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->orderBy('date')
            ->get();

        $fileName = 'attendance_'.$user->id.'_'.$base->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($attendances, $user) {
            $out = fopen('php://output', 'w');

            // Excel想定の文字化け対策
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($out, ['氏名', '日付', '出勤', '退勤', '休憩合計(分)', '備考']);

            foreach ($attendances as $a) {
                $breakMinutes = $a->breaks->sum(function ($b) {
                    if (! $b->break_in || ! $b->break_out) {
                        return 0;
                    }

                    return $b->break_out->diffInMinutes($b->break_in);
                });

                fputcsv($out, [
                    $user->name,
                    Carbon::parse($a->date)->format('Y-m-d'),
                    $a->clock_in ? $a->clock_in->format('H:i') : '',
                    $a->clock_out ? $a->clock_out->format('H:i') : '',
                    $breakMinutes,
                    $a->note ?? '',
                ]);
            }

            fclose($out);
        }, $fileName, [
            'content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
