<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $overrides = [])
    {
        return User::factory()->create(array_merge([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => 1,
        ], $overrides));
    }

    private function validAdminLoginPayload(array $overrides = [])
    {
        return array_merge([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/admin/login', $this->validAdminLoginPayload([
            'email' => '',
        ]));

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
        $this->assertGuest();
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/admin/login', $this->validAdminLoginPayload([
            'password' => '',
        ]));

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
        $this->assertGuest();
    }

    /** @test */
    public function 登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/admin/login', $this->validAdminLoginPayload([
            'email' => 'wrong@example.com',
        ]));

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest();
    }
}
