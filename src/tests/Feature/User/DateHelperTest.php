<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_画面上に表示されている日時が現在の日時と一致する(){

        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 17, 14, 30, 0)
        );

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('attendance/');
        // $response->assertStatus(200);

        $dt = Carbon::now();
        $time = $dt->format('H:i');
        $week = $dt->isoFormat('ddd');

        $expectedDate = $dt->format('Y年m月d日')."(". $week .")";
        $response->assertSee($expectedDate);

        $expectedTime = $dt->format('H:i');
        $response->assertSee($expectedTime);
    }
}

