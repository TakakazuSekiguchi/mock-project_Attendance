<?php

namespace Database\Factories;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'status' => 0,
            'reason' => 'テスト',
            'attendance_id' => Attendance::factory(),
            'after_clock_in' => null,
            'after_clock_out' => null,
            'approved_by' => null,
            'approved_at' => null
        ];
    }
}
