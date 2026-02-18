<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        $attendance = Attendance::factory()->create();
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'status' => 'pending',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'note' => '修正申請備考',
        ];
    }
}
