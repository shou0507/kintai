<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function loginVerifiedUser()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    /** @test */
    public function 勤務外の場合_勤怠ステータスが勤務外と表示される()
    {
        $this->loginVerifiedUser();

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('勤務外');
    }

    /** @test */
    public function 勤務中の場合_勤怠ステータスが勤務中と表示される()
    {
        $this->loginVerifiedUser();

        // 出勤
        $this->post('/attendance', ['action' => 'clock_in']);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }

    /** @test */
    public function 休憩中の場合_勤怠ステータスが休憩中と表示される()
    {
        $this->loginVerifiedUser();

        // 出勤 → 休憩入
        $this->post('/attendance', ['action' => 'clock_in']);
        $this->post('/attendance', ['action' => 'break_in']);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
    }

    /** @test */
    public function 退勤済の場合_勤怠ステータスが退勤済と表示される()
    {
        $this->loginVerifiedUser();

        // 出勤 → 退勤
        $this->post('/attendance', ['action' => 'clock_in']);
        $this->post('/attendance', ['action' => 'clock_out']);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
        $response->assertSeeText('お疲れ様でした。');
    }
}
