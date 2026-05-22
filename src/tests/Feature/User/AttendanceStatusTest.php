<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合、勤怠ステータスが正しく表示される(){
        $user = User::factory()->create();

        // 勤務外なので$attendanceを作成しない

        $response = $this->actingAs($user)->get('attendance/');

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合、勤怠ステータスが正しく表示される(){
        $user = User::factory()->create();

        // 出勤中データ作成
        Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);

        $response = $this->actingAs($user)->get('attendance/');

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合、勤怠ステータスが正しく表示される(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);

        // 休憩中データ作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->get('attendance/');

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合、勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        // 退勤済データ作成
        Attendance::factory()
            ->finished()
            ->create([
                'user_id' => $user->id,
            ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
