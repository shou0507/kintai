<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'テストユーザー',
                'password' => Hash::make('password'),
            ]
        );
        $userId = $user->id;

        // 今月を基準にする
        $base = now()->startOfMonth();

        // 勤怠を入れる日（まばらにする）
        $workDays = [1, 2, 5, 8, 12, 18, 22];

        foreach ($workDays as $day) {
            $date = $base->copy()->addDays($day - 1);

            $attendance = Attendance::create([
                'user_id' => $userId,
                'date' => $date->toDateString(),
                'clock_in' => $date->copy()->setTime(9, 0),
                'clock_out' => $date->copy()->setTime(18, 0),
                'note' => null,
            ]);

            // 休憩（昼1時間）
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_in' => $date->copy()->setTime(12, 0),
                'break_out' => $date->copy()->setTime(13, 0),
            ]);
        }
        // 前月にも1日だけ入れておく（切り替え確認用）
        $prev = $base->copy()->subMonth()->addDays(9);

        $attendance = Attendance::create([
            'user_id' => $userId,
            'date' => $prev->toString(),
            'clock_in' => $prev->copy()->setTime(10, 0),
            'clock_out' => $prev->copy()->setTime(17, 0),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_in' => $prev->copy()->setTime(13, 0),
            'break_out' => $prev->copy()->setTime(13, 30),
        ]);
    }
}
