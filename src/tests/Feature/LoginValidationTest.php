<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = [])
    {
        return User::factory()->create(array_merge([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    private function validLoginPayload(array $overrides = [])
    {
        return array_merge([
            'email' => 'taro@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションエラーになる()
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', $this->validLoginPayload([
            'email' => '',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションエラーになる()
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', $this->validLoginPayload([
            'password' => '',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /** @test */
    public function 登録内容と一致しない場合_エラーになる()
    {
        $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/login')->post('/login', $this->validLoginPayload([
            'email' => 'wrong@example.com',
        ]));

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }
}
