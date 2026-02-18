<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function loginVerifiedUser(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
    }

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $this->loginVerifiedUser();

        $before = $this->get('/attendance');
        $before->assertStatus(200);
        $before->assertSeeText('勤務外');
        $before->assertSeeText('出勤');

        $this->post('/attendance', ['action' => 'clock_in'])
            ->assertStatus(302);

        $after = $this->get('/attendance');
        $after->assertStatus(200);
        $after->assertSeeText('出勤中');
        $after->assertDontSeeText('勤務外');
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        // 出勤
        $this->post('/attendance', ['action' => 'clock_in'])->assertStatus(302);

        $att = Attendance::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $this->assertNotNull($att->clock_in);

        // 退勤
        $this->post('/attendance', ['action' => 'clock_out'])->assertStatus(302);

        $att->refresh();
        $this->assertNotNull($att->clock_out);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
        $response->assertDontSee('clock-in', false);

    }

    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0));

        $this->loginVerifiedUser();

        $this->post('/attendance', ['action' => 'clock_in'])->assertStatus(302);

        $list = $this->get('/attendance/list');
        $list->assertStatus(200);

        // list.blade.php は clock_in->format('H:i') なので 09:00 が正解
        $list->assertSeeText('09:00');

        Carbon::setTestNow();
    }
}
