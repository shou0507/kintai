<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::factory()->create();
    }

    private function createGeneralUser(array $override = [])
    {
        return User::factory()->create($override);
    }

    private function createAttendance(User $user, string $date, string $in = '09:00', string $out = '18:00')
    {
        return Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => Carbon::parse($date.' '.$in),
            'clock_out' => Carbon::parse($date.' '.$out),
            'note' => '備考',
        ]);
    }

    /** @test */
    public function 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createGeneralUser([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ]);
        $user2 = $this->createGeneralUser([
            'name' => '佐藤花子',
            'email' => 'hanako@example.com',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.staff.list'));

        $response->assertOk();

        $response->assertSee('山田太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('hanako@example.com');
    }

    /** @test */
    public function ユーザーの勤怠情報が正しく表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser([
            'name' => '田中一郎',
            'email' => 'ichiro@example.com',
        ]);

        // 2026-02 月の勤怠
        $this->createAttendance($user, '2026-02-10', '09:10', '18:05');
        $this->createAttendance($user, '2026-02-11', '10:00', '19:00');

        // 別月（混ざらない確認用）
        $this->createAttendance($user, '2026-03-01', '09:00', '18:00');

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'month' => '2026-02',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.staff_attendance.index');

        $records = $response->viewData('records');
        $this->assertNotNull($records);

        // その月の勤怠が入っている
        $this->assertTrue($records->has('2026-02-10'));
        $this->assertTrue($records->has('2026-02-11'));

        $this->assertFalse($records->has('2026-03-01'));

        $a = $records->get('2026-02-10');
        $this->assertSame('09:10', $a->clock_in->format('H:i'));
        $this->assertSame('18:05', $a->clock_out->format('H:i'));
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'month' => '2026-02',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.staff_attendance.index');

        $response->assertViewHas('prevMonth', fn ($m) => $m === '2026-01');
        $response->assertViewHas('nextMonth', fn ($m) => $m === '2026-03');
        $response->assertViewHas('currentMonthLabel', fn ($label) => $label === '2026年2月');
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'month' => '2026-12',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.staff_attendance.index');

        $response->assertViewHas('prevMonth', fn ($m) => $m === '2026-11');
        $response->assertViewHas('nextMonth', fn ($m) => $m === '2027-01');
        $response->assertViewHas('currentMonthLabel', fn ($label) => $label === '2026年12月');
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = $this->createAdminUser();
        $user = $this->createGeneralUser();

        // 既にその日の勤怠があるケース
        $attendance = $this->createAttendance($user, '2026-02-10', '09:00', '18:00');

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $user->id,
            'date' => '2026-02-10',
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.attendance.show', $attendance->id));

        $this->assertSame(1, Attendance::where('user_id', $user->id)->whereDate('date', '2026-02-10')->count());
    }
}
