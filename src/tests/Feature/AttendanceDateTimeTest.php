<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 現在の日時情報が_u_iと同じ形式で表示される()
    {
        // ① 現在時刻を固定
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 14, 30, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // ② 画面を開く
        $response = $this->get('/attendance');

        // ③ UIと同じ形式で表示されているか
        $response->assertSeeText('2026年2月6日（金）');
        $response->assertSeeText('14:30');

        // ④ 後始末（重要）
        Carbon::setTestNow();
    }
}
