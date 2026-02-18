<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            // 失敗時：/login にリダイレクト（302）＋エラーメッセージ
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // 成功時
        $request->session()->regenerate();

        $user = $request->user();

        return redirect()->intended($user->is_admin ? '/admin/attendance/list' : '/attendance');
    }
}
