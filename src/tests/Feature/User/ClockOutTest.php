<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが正しく機能する(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);
        $response = $this->actingAs($user)->get('attendance/');

        // 退勤ボタンの表示確認されたとみなす
        $response->assertSee('退勤');

        // 退勤ボタンを押した際の処理
        $response = $this->post('/attendance/clock-out');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(){
    
        $user = User::factory()->create();

        // 勤務外でログインしている状態
        $response = $this->actingAs($user)->get('attendance/');

        // 出勤ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 10, 00, 0)
        );

        $response = $this->post('/attendance/clock-in');
        $response = $this->actingAs($user)->get('attendance/');

        // 退勤ボタンの表示確認されたとみなす
        $response->assertSee('退勤');

        // 退勤ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 19, 30, 0)
        );

        $response = $this->post('/attendance/clock-out');
        $response->assertRedirect('/attendance');

        // DBに登録された勤怠時刻
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => '2026-05-20 10:00:00',
            'clock_out' => '2026-05-20 19:30:00',
        ]);

        // 勤怠一覧画面に遷移
        $response = $this->post('/attendance/list');

        // 出勤時刻の表示確認
        $response->assertSee('19:30');
    }
}
