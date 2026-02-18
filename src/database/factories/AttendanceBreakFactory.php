<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition(): array
    {
        $date = now()->toDateString();

        return [
            'attendance_id' => Attendance::factory(),

            'break_in' => $date.' 12:00:00',
            'break_out' => $date.' 13:00:00',
        ];
    }
}
