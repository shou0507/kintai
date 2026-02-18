<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceCorrectionFeatureTest extends TestCase
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

    private function createAttendance(User $user, string $date = '2026-02-10')
    {
        return Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => Carbon::parse($date.' 09:00'),
            'clock_out' => Carbon::parse($date.' 18:00'),
            'note' => '備考',
        ]);
    }

    private function createCorrection(array $override = [])
    {
        return AttendanceCorrection::factory()->create(array_merge([
            'status' => 'pending',
        ], $override));
    }

    /** @test */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createGeneralUser(['name' => '山田太郎']);
        $user2 = $this->createGeneralUser(['name' => '佐藤花子']);

        $a1 = $this->createAttendance($user1);
        $a2 = $this->createAttendance($user2);

        $pending1 = $this->createCorrection([
            'user_id' => $user1->id,
            'attendance_id' => $a1->id,
            'status' => 'pending',
        ]);
        $pending2 = $this->createCorrection([
            'user_id' => $user2->id,
            'attendance_id' => $a2->id,
            'status' => 'pending',
        ]);

        $this->createCorrection([
            'user_id' => $user1->id,
            'attendance_id' => $a1->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.stamp_correction_request.list'));

        $response->assertOk();
        $response->assertViewIs('admin.stamp_correction_requests.index');

        $response->assertViewHas('pendingRequests', function ($list) use ($pending1, $pending2) {
            return $list->count() === 2
                && $list->contains('id', $pending1->id)
                && $list->contains('id', $pending2->id);
        });

        $response->assertViewHas('approvedRequests', function ($list) {
            return $list->count() === 1;
        });
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = $this->createAdminUser();

        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user);

        $approved1 = $this->createCorrection([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
        ]);
        $approved2 = $this->createCorrection([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
        ]);

        $this->createCorrection([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.stamp_correction_request.list'));

        $response->assertOk();
        $response->assertViewIs('admin.stamp_correction_requests.index');

        $response->assertViewHas('approvedRequests', function ($list) use ($approved1, $approved2) {
            return $list->contains('id', $approved1->id)
                && $list->contains('id', $approved2->id);
        });

        $response->assertViewHas('pendingRequests', function ($list) {
            return $list->count() === 1;
        });
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = $this->createAdminUser();

        $user = $this->createGeneralUser(['name' => '田中一郎']);
        $attendance = $this->createAttendance($user);

        $scr = $this->createCorrection([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'date' => $attendance->date->format('Y-m-d'),
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'note' => '申請備考',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.stamp_correction_request.approve.show', $scr->id));

        $response->assertOk();
        $response->assertViewIs('admin.stamp_correction_requests.approve');

        $response->assertViewHas('scr', function ($v) use ($scr) {
            return $v->id === $scr->id
                && $v->user !== null
                && $v->attendance !== null
                && $v->status === 'pending';
        });
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = $this->createAdminUser();

        $user = $this->createGeneralUser();
        $attendance = $this->createAttendance($user, '2026-02-10');

        $scr = $this->createCorrection([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'date' => $attendance->date->format('Y-m-d'),
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'note' => '承認後備考',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.stamp_correction_request.approve.update', $scr->id));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.stamp_correction_request.list'));

        $scr->refresh();
        $attendance->refresh();

        $this->assertSame('approved', $scr->status);
        $this->assertSame('10:00', $attendance->clock_in->format('H:i'));
        $this->assertSame('19:00', $attendance->clock_out->format('H:i'));
        $this->assertSame('承認後備考', $attendance->note);
    }
}
