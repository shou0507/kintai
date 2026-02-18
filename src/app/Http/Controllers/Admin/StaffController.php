<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    // 一般ユーザー
    public function index()
    {
        $users = User::where('is_admin', 0)
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', [
            'users' => $users,
        ]);
    }
}
