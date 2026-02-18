<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailEditTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create();
    }

    private function createGeneralUser(): User
    {
        return User::factory()->create();
    }

    private function createAttendance(User $user, string $date = '2026-02-10'): Attendance
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
    public function 出勤時間が退勤時間以上の場合はエラーになる(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'note' => '修正',
        ]);

        // ※メッセージ一致で検証したい場合は、AdminAttendanceRequest 側の文言と合わせてください
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['clock_in', 'clock_out']);
    }

    /** @test */
    public function 休憩開始時間が出勤より前または退勤より後の場合はエラーになる(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '18:30', // 退勤より後
            'break1_out' => '18:40',
            'note' => '修正',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['break1_in']);
    }

    /** @test */
    public function 休憩終了時間が退勤より後の場合はエラーになる(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin);

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break1_in' => '12:00',
            'break1_out' => '18:30', // 退勤より後
            'note' => '修正',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['break1_out']);
    }

    /** @test */
    public function 備考が未入力の場合はエラーになる(): void
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
        $response->assertSessionHasErrors(['note']);
    }

    /** @test */
    public function 勤怠と休憩が正しく更新される(): void
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

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'break1_in' => '14:00',
            'break1_out' => '14:30',
            'break2_in' => '16:00',
            'break2_out' => '16:15',
            'note' => '更新後備考',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.attendance.show', $attendance->id));

        $attendance->refresh();

        $this->assertSame('2026-02-10 10:00:00', $attendance->clock_in->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-10 19:00:00', $attendance->clock_out->format('Y-m-d H:i:s'));
        $this->assertSame('更新後備考', $attendance->note);

        $breaks = $attendance->breaks()->orderBy('break_in')->get();
        $this->assertCount(2, $breaks);
    }

    /** @test */
    public function 日付指定で勤怠が存在しない場合は新規作成され詳細画面へ遷移する(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();

        $this->actingAs($admin);

        $date = '2026-02-10';

        $response = $this->get(route('admin.attendance.staff', ['id' => $user->id, 'date' => $date]));

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        $this->assertNotNull($attendance);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.attendance.show', $attendance->id));
    }
}
