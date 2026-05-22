<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-20 12:00:00',
            'break_end' => '2026-05-20 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/attendance/list');

        $dt = Carbon::now();
        $expectedMonth = $dt->format('Y/m');
        $response->assertSee($expectedMonth);
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-04-21 09:00:00',
            'clock_out' => '2026-04-21 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-04-21 12:00:00',
            'break_end' => '2026-04-21 13:00:00',
        ]);

        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 10, 30, 0)
        );

        $response = $this->actingAs($user)
            ->get('/attendance/list');
        
        // 2026年5月を表示中に「前月」を押す想定
        $response = $this->get('/attendance/list?month=2026-04');
        $response->assertStatus(200);
        
        $response->assertSee('2026/04'); // 前月の表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_「翌月」を押下した時に表示月の翌月の情報が表示される()
    {
        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-06-21 09:00:00',
            'clock_out' => '2026-06-21 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-06-21 12:00:00',
            'break_end' => '2026-06-21 13:00:00',
        ]);

        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 10, 30, 0)
        );

        $response = $this->actingAs($user)
            ->get('/attendance/list');
        
        // 2026年5月を表示中に「翌月」を押す想定
        $response = $this->get('/attendance/list?month=2026-06');
        $response->assertStatus(200);
        
        $response->assertSee('2026/06'); // 前月の表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-20 12:00:00',
            'break_end' => '2026-05-20 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);
        $response->assertSee('2026年'); // 年表示
        $response->assertSee('5月20日'); // 月表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('12:00'); // 休憩入時刻
        $response->assertSee('13:00'); // 休憩戻時刻
    }
}
