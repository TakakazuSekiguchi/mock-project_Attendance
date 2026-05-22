<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する(){
        $user = User::factory()->create();

        // 勤務外でログインしている状態
        $response = $this->actingAs($user)->get('attendance/');

        // 出勤ボタンの表示確認されたとみなす
        $response->assertSee('出勤');

        // 出勤ボタンを押した際の処理
        $response = $this->post('/attendance/clock-in');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
    }

    public function test_出勤は一日一回のみできる(){
        $user = User::factory()->create();

        // 退勤済データ作成
        Attendance::factory()
            ->finished()
            ->create([
                'user_id' => $user->id,
            ]);

        $response = $this->actingAs($user)->get('attendance/');

        // 出勤ボタンが表示されていない状態
        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(){

        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 17, 10, 30, 0)
        );
    
        $user = User::factory()->create();

        // 勤務外でログインしている状態
        $response = $this->actingAs($user)->get('attendance/');

        // 出勤ボタンを押した際の処理
        $response = $this->post('/attendance/clock-in');

        $dt = Carbon::now();
        $expectedTime = $dt->format('H:i');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        // 勤怠一覧画面に遷移
        $response = $this->post('/attendance/list');

        // 出勤時刻の表示確認
        $response->assertSee($expectedTime);
    }
}
