<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function loginVerifiedUser(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
    }

    private function clockIn(): void
    {
        $this->post('/attendance', ['action' => 'clock_in'])->assertStatus(302);
    }

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $this->loginVerifiedUser();
        $this->clockIn();

        $before = $this->get('/attendance');
        $before->assertStatus(200);
        $before->assertSeeText('出勤中');
        $before->assertSeeText('退勤');

        // 退勤（←ここが一番大事）
        $this->post('/attendance', ['action' => 'clock_out'])->assertStatus(302);

        $after = $this->get('/attendance');
        $after->assertStatus(200);
        $after->assertSeeText('退勤済');
        $after->assertSeeText('お疲れ様でした。');

        // finishedでは退勤ボタンが無い
        $after->assertDontSee('clock-out', false);
    }

    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $this->loginVerifiedUser();

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0));
        $this->post('/attendance', ['action' => 'clock_in'])->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 18, 0, 0));
        $this->post('/attendance', ['action' => 'clock_out'])->assertStatus(302);

        $list = $this->get('/attendance/list');
        $list->assertStatus(200);
        $list->assertSeeText('18:00');

        Carbon::setTestNow();
    }
}
