<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakFeatureTest extends TestCase
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

    private function clockIn()
    {
        $this->post('/attendance', ['action' => 'clock_in'])->assertStatus(302);
    }

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        $this->loginVerifiedUser();
        $this->clockIn();

        // 出勤中：休憩入が見える
        $before = $this->get('/attendance');
        $before->assertStatus(200);
        $before->assertSeeText('出勤中');
        $before->assertSeeText('休憩入');

        // 休憩入
        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);

        // 休憩中：休憩戻が見える
        $after = $this->get('/attendance');
        $after->assertStatus(200);
        $after->assertSeeText('休憩中');
        $after->assertSeeText('休憩戻');
    }

    /** @test */
    public function 休憩は一日に何回でもできる()
    {
        $this->loginVerifiedUser();
        $this->clockIn();

        // 1回目 休憩
        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);
        $this->post('/attendance', ['action' => 'break_back'])->assertStatus(302);

        // 出勤中に戻っているので、再び「休憩入」が出る
        $page = $this->get('/attendance');
        $page->assertStatus(200);
        $page->assertSeeText('出勤中');
        $page->assertSeeText('休憩入');
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $this->loginVerifiedUser();
        $this->clockIn();

        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);

        // 休憩中：休憩戻が見える
        $during = $this->get('/attendance');
        $during->assertStatus(200);
        $during->assertSeeText('休憩中');
        $during->assertSeeText('休憩戻');

        // 休憩戻
        $this->post('/attendance', ['action' => 'break_back'])->assertStatus(302);

        // 出勤中に戻る
        $after = $this->get('/attendance');
        $after->assertStatus(200);
        $after->assertSeeText('出勤中');
        $after->assertSeeText('休憩入');
    }

    /** @test */
    public function 休憩戻は一日に何回でもできる()
    {
        $this->loginVerifiedUser();
        $this->clockIn();

        // 1回目
        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);
        $this->post('/attendance', ['action' => 'break_back'])->assertStatus(302);

        // 2回目（再度 休憩入 して休憩中へ）
        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);

        $page = $this->get('/attendance');
        $page->assertStatus(200);
        $page->assertSeeText('休憩中');
        $page->assertSeeText('休憩戻');
    }

    /** @test */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 0, 0));

        $user = $this->loginVerifiedUser();
        $this->clockIn();

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 10, 0));
        $this->post('/attendance', ['action' => 'break_in'])->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 9, 25, 0));
        $this->post('/attendance', ['action' => 'break_back'])->assertStatus(302);

        $list = $this->get('/attendance/list');
        $list->assertStatus(200);

        $list->assertSeeText('0:15');

        Carbon::setTestNow();
    }
}
