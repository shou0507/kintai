<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $pendingRequests = AttendanceCorrection::where('user_id', $userId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $approvedRequests = AttendanceCorrection::where('user_id', $userId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('stamp_correction_requests.index', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ]);
    }
}
