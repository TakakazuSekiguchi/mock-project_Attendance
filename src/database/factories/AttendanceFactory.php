<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // Attendanceに紐づくUserを自動作成
            'user_id' => User::factory(),

            // 初期状態（勤務外を想定）
            'clock_in' => null,
            'clock_out' => null,
        ];
    }

    // 出勤中
    public function working()
    {
        return $this->state(function () {
            return [
                'clock_in' => now(),
                'clock_out' => null,
            ];
        });
    }

    // 退勤済
    public function finished()
    {
        return $this->state(function () {
            return [
                'clock_in' => now()->subHours(8),
                'clock_out' => now(),
            ];
        });
    }
}
