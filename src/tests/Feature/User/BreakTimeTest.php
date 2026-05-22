<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);
        $response = $this->actingAs($user)->get('attendance/');

        // 休憩入ボタンの表示確認されたとみなす
        $response->assertSee('休憩入');

        // 休憩入ボタンを押した際の処理
        $response = $this->post('/attendance/break-start');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    public function test_休憩は一日に何回でもできる_休憩1度目(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);
        $response = $this->actingAs($user)->get('attendance/');

        // 休憩入ボタンの表示確認されたとみなす
        $response->assertSee('休憩入');

        // 休憩入ボタンを押した際の処理
        $response = $this->post('/attendance/break-start');

        // $response->assertRedirect('/attendance');
        // 上記のコードだとリダイレクトするだけでhtmlは表示されないので、以下の書き方にする
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);

        // 休憩戻ボタンの表示確認されたとみなす
        $response->assertSee('休憩戻');

        // 休憩戻ボタンを押した際の処理
        $response = $this->post('/attendance/break-end');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);

        // 再び休憩入が表示される
        $response->assertSee('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能する(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);
        $response = $this->actingAs($user)->get('attendance/');
        $response->assertSee('休憩入');

        // 休憩入ボタンを押した際の処理
        $response = $this->post('/attendance/break-start');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);

        // 休憩戻ボタンの表示確認されたとみなす
        $response->assertSee('休憩戻');

        // 休憩戻ボタンを押した際の処理
        $response = $this->post('/attendance/break-end');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    public function test_休憩は一日に何回でもできる_休憩2度目(){
        $user = User::factory()->create();

        // 出勤中データ作成
        $attendance = Attendance::factory()
            ->working()
            ->create([
                'user_id' => $user->id,
            ]);
        $response = $this->actingAs($user)->get('attendance/');
        $response->assertSee('休憩入');

        // 1度目の休憩入ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 12, 0, 0)
        );

        $response = $this->post('/attendance/break-start');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
        $response->assertSee('休憩戻');

        // 1度目の休憩戻ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 12, 30, 0)
        );
        
        $response = $this->post('/attendance/break-end');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);
        $response->assertSee('休憩入');

        // 2度目の休憩入ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 15, 0, 0)
        );

        $response = $this->post('/attendance/break-start');
        $response = $this->actingAs($user)->get('attendance/');

        // 休憩入ボタンが再び押されたので、レコードがもう一つ追加され2行になる
        $this->assertDatabaseCount('break_times', 2);

        // 休憩戻がnullの状態で登録される
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        // 再び休憩戻が表示される
        $response->assertSee('休憩戻');
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(){
    
        $user = User::factory()->create();

        // 勤務外でログインしている状態
        $response = $this->actingAs($user)->get('attendance/');

        // 出勤ボタンを押した際の処理
        $response = $this->post('/attendance/clock-in');
        $response = $this->actingAs($user)->get('attendance/');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);

        $response->assertSee('休憩入');

        // 1度目の休憩入ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 12, 0, 0)
        );

        $response = $this->post('/attendance/break-start');
        $response = $this->actingAs($user)->get('attendance/');
        $response->assertSee('休憩戻');

        // 1度目の休憩戻ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 12, 30, 0)
        );
        
        $response = $this->post('/attendance/break-end');
        $response = $this->actingAs($user)->get('attendance/');
        $response->assertSee('休憩入');

        // 2度目の休憩入ボタンを押した際の処理
        Carbon::setTestNow(
            Carbon::create(2026, 5, 19, 15, 0, 0)
        );

        $response = $this->post('/attendance/break-start');
        $response = $this->actingAs($user)->get('attendance/');
        $this->assertDatabaseCount('break_times', 2);
        $response->assertSee('休憩戻');

        // DBに登録された休憩時刻
        $this->assertDatabaseHas('break_times', [
            'break_start' => '2026-05-19 12:00:00',
            'break_end' => '2026-05-19 12:30:00',
        ]);

        $this->assertDatabaseHas('break_times', [
            'break_start' => '2026-05-19 15:00:00',
            'break_end' => null,
        ]);
        
        // 勤怠一覧画面に遷移
        $response = $this->post('/attendance/list');

        // 休憩時刻の表示確認
        $response->assertSee('00:45');
    }
}
