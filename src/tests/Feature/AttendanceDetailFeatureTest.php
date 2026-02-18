<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function loginVerifiedUser(string $name = 'テスト太郎'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        return $user;
    }

    private function createAttendanceWithBreak(User $user): Attendance
    {
        $att = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-02',
            'clock_in' => Carbon::create(2026, 2, 2, 9, 0, 0),
            'clock_out' => Carbon::create(2026, 2, 2, 18, 0, 0),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $att->id,
            'break_in' => Carbon::create(2026, 2, 2, 12, 0, 0),
            'break_out' => Carbon::create(2026, 2, 2, 12, 30, 0),
        ]);

        return $att;
    }

    /** @test */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $user = $this->loginVerifiedUser('山田太郎');
        $att = $this->createAttendanceWithBreak($user);

        $res = $this->get('/attendance/detail/'.$att->id);
        $res->assertStatus(200);
        $res->assertSeeText('山田太郎');
    }

    /** @test */
    public function 勤怠詳細画面の日付が選択した日付になっている()
    {
        $user = $this->loginVerifiedUser();
        $att = $this->createAttendanceWithBreak($user);

        $res = $this->get('/attendance/detail/'.$att->id);
        $res->assertStatus(200);

        $res->assertSeeText('2026年');
        $res->assertSeeText('2月2日');
    }

    /** @test */
    public function 出勤退勤に記されている時間が打刻と一致している()
    {
        $user = $this->loginVerifiedUser();
        $att = $this->createAttendanceWithBreak($user);

        $res = $this->get('/attendance/detail/'.$att->id);
        $res->assertStatus(200);

        $res->assertSee('value="09:00"', false);
        $res->assertSee('value="18:00"', false);
    }

    /** @test */
    public function 休憩に記されている時間が打刻と一致している()
    {
        $user = $this->loginVerifiedUser();
        $att = $this->createAttendanceWithBreak($user);

        $res = $this->get('/attendance/detail/'.$att->id);
        $res->assertStatus(200);

        $res->assertSee('12:00', false);
        $res->assertSee('12:30', false);
    }
}
