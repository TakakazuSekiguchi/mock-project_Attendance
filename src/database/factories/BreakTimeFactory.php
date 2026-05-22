<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Attendance;

class BreakTimeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => now(),
            'break_end' => null,
        ];
    }

    // 休憩終了済み
    public function finished()
    {
        return $this->state(fn () => [
            'break_start' => now()->subHour(),
            'break_end' => now(),
        ]);
    }
}