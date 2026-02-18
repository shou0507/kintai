<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListFeatureTest extends TestCase
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

    private function createAttendance(User $user, string $dateYmd, string $inHi, ?string $outHi = null)
    {
        $clockIn = Carbon::createFromFormat('Y-m-d H:i', "{$dateYmd} {$inHi}");
        $clockOut = $outHi ? Carbon::createFromFormat('Y-m-d H:i', "{$dateYmd} {$outHi}") : null;

        return Attendance::create([
            'user_id' => $user->id,
            'date' => $dateYmd,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'note' => null,
        ]);
    }

    /** @test */
    public function 自分が行った勤怠情報が全て表示されている()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        $me = $this->loginVerifiedUser();
        $other = User::factory()->create(['email_verified_at' => now()]);

        // 自分の勤怠（2件）
        $this->createAttendance($me, '2026-02-06', '09:00', '18:00');
        $this->createAttendance($me, '2026-02-07', '10:00', '19:00');

        // 他人の勤怠（同月・見えてはいけない）
        $this->createAttendance($other, '2026-02-06', '07:00', '16:00');

        $res = $this->get('/attendance/list?month=2026-02');
        $res->assertStatus(200);

        // 自分の情報が表示される
        $res->assertSeeText('02/06');
        $res->assertSeeText('09:00');
        $res->assertSeeText('18:00');

        $res->assertSeeText('02/07');
        $res->assertSeeText('10:00');
        $res->assertSeeText('19:00');

        // 他人の時刻は表示されない
        $res->assertDontSeeText('07:00');
        $res->assertDontSeeText('16:00');

        Carbon::setTestNow();
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        $this->loginVerifiedUser();

        $res = $this->get('/attendance/list');
        $res->assertStatus(200);

        $res->assertSeeText('2026/02');

        Carbon::setTestNow();
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        $me = $this->loginVerifiedUser();

        // 1月と2月にデータを作る
        $this->createAttendance($me, '2026-01-10', '09:00', '18:00'); // 前月
        $this->createAttendance($me, '2026-02-06', '10:00', '19:00'); // 当月

        // 前月ページを表示（＝「前月」リンク先と同じ）
        $res = $this->get('/attendance/list?month=2026-01');
        $res->assertStatus(200);

        $res->assertSeeText('2026/01');
        $res->assertSeeText('01/10');
        $res->assertSeeText('09:00');
        $res->assertSeeText('18:00');

        // 2月のデータは表示対象外（月が違う）
        $res->assertDontSeeText('02/06');
        $res->assertDontSeeText('10:00');
        $res->assertDontSeeText('19:00');

        Carbon::setTestNow();
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        $me = $this->loginVerifiedUser();

        $this->createAttendance($me, '2026-02-06', '09:00', '18:00'); // 当月
        $this->createAttendance($me, '2026-03-05', '11:00', '20:00'); // 翌月

        $res = $this->get('/attendance/list?month=2026-03');
        $res->assertStatus(200);

        $res->assertSeeText('2026/03');
        $res->assertSeeText('03/05');
        $res->assertSeeText('11:00');
        $res->assertSeeText('20:00');

        // 2月分は表示されない
        $res->assertDontSeeText('02/06');
        $res->assertDontSeeText('09:00');
        $res->assertDontSeeText('18:00');

        Carbon::setTestNow();
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        $me = $this->loginVerifiedUser();
        $att = $this->createAttendance($me, '2026-02-06', '09:00', '18:00');

        $list = $this->get('/attendance/list?month=2026-02');
        $list->assertStatus(200);

        $list->assertSee('/attendance/detail/'.$att->id, false);

        $detail = $this->get('/attendance/detail/'.$att->id);
        $detail->assertStatus(200);

        Carbon::setTestNow();
    }
}
