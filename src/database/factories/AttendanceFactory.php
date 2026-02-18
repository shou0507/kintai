<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = now()->toDateString();

        return [
            'user_id' => User::factory(),
            'date' => $date,
            'clock_in' => $date.' 09:00:00',
            'clock_out' => $date.' 18:00:00',
            'note' => '備考',
        ];
    }
}
