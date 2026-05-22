<?php

namespace Database\Factories;
use App\Models\StampCorrectionRequest;
use App\Models\BreakTime;

use Illuminate\Database\Eloquent\Factories\Factory;

class BreakRequestDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'stamp_correction_request_id' => StampCorrectionRequest::factory(),
            'break_time_id' => BreakTime::factory(),
            'after_start' => null,
            'after_end' => null,
        ];
    }
}
