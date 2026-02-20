<?php

namespace App\Http\Controllers\Admin\StampCorrection;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index()
    {
        // 承認待ち：全一般ユーザーの未承認
        $pendingRequests = AttendanceCorrection::with('user')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        // 承認済み：全一般ユーザーの承認済み
        $approvedRequests = AttendanceCorrection::with('user')
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.stamp_correction_requests.index', compact('pendingRequests', 'approvedRequests'));
    }

    public function show($id)
    {
        $scr = AttendanceCorrection::with(['user', 'attendance'])
            ->findOrFail($id);

        return view('admin.stamp_correction_requests.approve', compact('scr'));
    }

    public function update(Request $request, $id)
    {
        $scr = AttendanceCorrection::with('attendance')
            ->findOrFail($id);

        if ($scr->status !== 'pending') {
            return redirect('/stamp_correction_request/list')
                ->with('message', 'この申請はすでに処理済みです。');
        }

        if (! $scr->attendance) {
            return redirect()->route('admin.stamp_correction_request.list')
                ->with('message', '勤怠情報が見つかりません。');
        }

        // 申請ステータスを承認済みに
        $scr->update(['status' => 'approved']);

        $dateStr = Carbon::parse($scr->attendance->date)->format('Y-m-d');

        $updates = [
            'clock_in' => $scr->clock_in
                ? Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$scr->clock_in}")
                : $scr->attendance->clock_in,

            'clock_out' => $scr->clock_out
                ? Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$scr->clock_out}")
                : $scr->attendance->clock_out,

            'note' => $scr->note ?? $scr->attendance->note,
        ];

        $scr->attendance->update($updates);

        return redirect()->route('admin.stamp_correction_request.list')
            ->with('message', '承認しました。');
    }
}
