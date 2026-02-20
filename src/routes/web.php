<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StampCorrection\RequestController as AdminStampCorrectionRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =====================================================
// 一般ユーザー ログイン（guest）
// =====================================================
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('login.store');

// =====================================================
// 管理者ログイン（guest）
// =====================================================
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login');

Route::post('/admin/login', [AdminLoginController::class, 'store'])
    ->middleware(['guest'])
    ->name('admin.login.store');

// =====================================================
// ログアウト（auth）
// =====================================================
// ※ guard が web しかないので Auth::logout() は共通になります
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

Route::post('/admin/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/admin/login');
})->middleware('auth')->name('admin.logout');

// =====================================================
// 一般ユーザー（auth）
// =====================================================
Route::middleware('auth', 'verified')->group(function () {

    // 一般ユーザー：勤怠
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance', [AttendanceController::class, 'store']);

    Route::get('/attendance/list', [AttendanceController::class, 'show'])->name('attendance.list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'storeCorrection'])->name('attendance.detail.store');

    // 一般ユーザー：申請（あなたの一般用コントローラがある場合はここに置く）
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('stamp_correction_request.list');
    Route::post('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'store'])->name('stamp_correction_request.store');
});

// =====================================================
// 管理者（auth）
// =====================================================
// 本当は ->middleware(['auth','admin']) が理想（admin判定）
// まずは /admin に寄せて一般URLと衝突しないようにする
Route::prefix('admin')->middleware('auth')->group(function () {

    // 申請
    Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index'])
        ->name('admin.stamp_correction_request.list');

    Route::post('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'store'])
        ->name('admin.stamp_correction_request.store');

    Route::get('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'show'])
        ->name('admin.stamp_correction_request.approve.show');

    Route::post('/stamp_correction_request/approve/{id}', [AdminStampCorrectionRequestController::class, 'update'])
        ->name('admin.stamp_correction_request.approve.update');

    // 勤怠/スタッフ（元のやつを移動）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffIndex'])->name('admin.attendance.staff');

    // CSV出力
    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'staffCsv'])->name('admin.attendance.staff.csv');
});

Route::get('/email/verify', function () {
    return view('auth.email'); // 誘導画面のBlade
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/attendance'); // 認証後に行かせたい先
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
