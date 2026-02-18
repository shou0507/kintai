<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::factory()->create();
    }

    private function createUser()
    {
        return User::factory()->create();
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
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = $this->createAdminUser();
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        if ($user1->id > $user2->id) {
            [$user1, $user2] = [$user2, $user1];
        }

        $targetDate = '2026-02-10';
        $otherDate = '2026-02-09';

        $a1 = $this->createAttendance($user1, $targetDate, '09:10', '18:05');
        $a2 = $this->createAttendance($user2, $targetDate, '10:00', '19:00');

        $this->createAttendance($user1, $otherDate, '09:00', '18:00');

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.list', ['date' => $targetDate]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.list');

        // 対象日の勤怠が「全ユーザー分」取れていて、別日の勤怠が混ざらない
        $response->assertViewHas('attendances', function ($attendances) use ($a1, $a2) {
            if (! ($attendances instanceof \Illuminate\Support\Collection)) {
                return false;
            }
            if ($attendances->count() !== 2) {
                return false;
            }

            $ids = $attendances->pluck('id')->values()->all();

            return $ids === [$a1->id, $a2->id];
        });

        $response->assertViewHas('attendances', function ($attendances) use ($a1) {
            $row = $attendances->firstWhere('id', $a1->id);

            return $row && $row->user !== null;
        });
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = $this->createAdminUser();

        // 今日を固定
        Carbon::setTestNow(Carbon::parse('2026-02-10 12:00:00'));

        $this->actingAs($admin);

        // dateパラメータなし＝今日
        $response = $this->get(route('admin.attendance.list'));

        $response->assertOk();
        $response->assertViewHas('date', function ($date) {
            return $date instanceof Carbon && $date->format('Y-m-d') === '2026-02-10';
        });
    }

    /** @test */
    public function 前日を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();

        $prevDate = '2026-02-09';
        $prevAttendance = $this->createAttendance($user, $prevDate, '09:30', '18:30');

        $this->actingAs($admin);

        // 「前日」ボタン押下は ?date=前日
        $response = $this->get(route('admin.attendance.list', ['date' => $prevDate]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.list');

        $response->assertViewHas('date', fn ($date) => $date instanceof Carbon && $date->format('Y-m-d') === $prevDate);
        $response->assertViewHas('prevDate', fn ($d) => $d === Carbon::parse($prevDate)->subDay()->format('Y-m-d'));
        $response->assertViewHas('nextDate', fn ($d) => $d === Carbon::parse($prevDate)->addDay()->format('Y-m-d'));

        $response->assertViewHas('attendances', function ($attendances) use ($prevAttendance) {
            return $attendances->count() === 1
                && $attendances->first()->id === $prevAttendance->id;
        });
    }

    /** @test */
    public function 翌日を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();

        $nextDate = '2026-02-11';
        $nextAttendance = $this->createAttendance($user, $nextDate, '08:50', '17:50');

        $this->actingAs($admin);

        // 「翌日」ボタン押下は ?date=翌日
        $response = $this->get(route('admin.attendance.list', ['date' => $nextDate]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.list');

        $response->assertViewHas('date', fn ($date) => $date instanceof Carbon && $date->format('Y-m-d') === $nextDate);
        $response->assertViewHas('prevDate', fn ($d) => $d === Carbon::parse($nextDate)->subDay()->format('Y-m-d'));
        $response->assertViewHas('nextDate', fn ($d) => $d === Carbon::parse($nextDate)->addDay()->format('Y-m-d'));

        $response->assertViewHas('attendances', function ($attendances) use ($nextAttendance) {
            return $attendances->count() === 1
                && $attendances->first()->id === $nextAttendance->id;
        });
    }
}
