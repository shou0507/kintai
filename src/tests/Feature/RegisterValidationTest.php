<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = [])
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    /** @test */
    public function 名前が未入力の場合_バリデーションエラーになる()
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'name' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションエラーになる()
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'email' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションエラーになる()
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function パスワードが8文字未満の場合_バリデーションエラーになる()
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function パスワード確認が一致しない場合_バリデーションエラーになる()
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'password123',
            'password_confirmation' => 'password999',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password_confirmation']);
    }

    /** @test */
    public function 正常入力の場合_ユーザー情報が_d_bに保存され_認証メール通知が送られる()
    {
        Notification::fake();

        $payload = $this->validPayload();

        $response = $this->post('/register', $payload);

        // ユーザーが作成されていること
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テスト太郎',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        // メール未認証（email_verified_atがnull）であること
        $this->assertNull($user->email_verified_at);

        // 認証メール（VerifyEmail通知）が送られていること
        Notification::assertSentTo($user, VerifyEmail::class);

        // Fortify/Laravelの構成によって遷移先が違うので、最低限「リダイレクトされる」ことだけ確認
        $response->assertStatus(302);
    }
}
