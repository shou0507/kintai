<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse
            {
                public function toResponse($request)
                {
                    $user = $request->user();

                    // 例：is_admin カラムで判定（あなたの設計に合わせて変更）
                    return redirect($user->is_admin ? '/admin/attendance/list' : '/attendance');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(fn () => view('auth.register'));

        Fortify::loginView(function (Request $request) {
            // /admin/login なら管理者ログイン画面
            if ($request->is('admin/login')) {
                return view('admin.auth.login');
            }

            // それ以外は一般ログイン画面
            return view('auth.login');
        });
    }
}
