<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録後に認証メールが送信される(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $res = $this->post(route('verification.send'));
        $res->assertStatus(302);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証誘導画面で認証メール再送ができる(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $res = $this->post('/email/verification-notification');

        $res->assertStatus(302);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証を完了すると勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $res = $this->get($verificationUrl);

        $res->assertStatus(302);
        $res->assertRedirect('/attendance');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}
