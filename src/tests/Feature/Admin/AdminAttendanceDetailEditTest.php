<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailEditTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::factory()->create();
    }

    private function createGeneralUser()
    {
        return User::factory()->create();
    }

    private function createAttendance(User $user, string $date = '2026-02-10')
    {
        return Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => Carbon::parse($date.' 09:00'),
            'clock_out' => Carbon::parse($date.' 18:00'),
            'note' => '初期備考',
        ]);
    }

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in' => Carbon::parse('2026-02-10 12:00'),
            'break_out' => Carbon::parse('2026-02-10 13:00'),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.show', $attendance->id));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.detail');

        $response->assertViewHas('attendance', function ($a) use ($attendance) {
            return $a->id === $attendance->id
                && $a->user !== null
                && $a->breaks !== null
                && $a->clock_in->format('H:i') === '09:00'
                && $a->clock_out->format('H:i') === '18:00';
        });

        $response->assertViewHas('user', function ($u) use ($user) {
            return $u->id === $user->id;
        });
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $msg = '出勤時間もしくは退勤時間が不適切な値です';

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'note' => '修正',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'clock_in' => $msg,
            'clock_out' => $msg,
        ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '18:30',
            'break1_out' => '18:40',
            'note' => '修正',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'break1_in' => '休憩時間が不適切な値です',
        ]);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '12:00',
            'break1_out' => '18:30',
            'note' => '修正',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'break1_out' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /** @test */
    public function 備考欄が未入力の場合エラーメッセージが表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }
}
