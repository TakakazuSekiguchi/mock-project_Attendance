<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        // 勤怠データを作成（ユーザー1）
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-05-20 12:00:00',
            'break_end' => '2026-05-20 13:00:00',
        ]);

        // 勤怠データを作成（ユーザー2）
        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'clock_in' => '2026-05-20 09:30:00',
            'clock_out' => '2026-05-20 19:30:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-05-20 13:00:00',
            'break_end' => '2026-05-20 14:00:00',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();

        // 管理者ログイン
        $this->actingAs($adminuser, 'admin');

        // 管理者ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        $response->assertStatus(200);

        // ユーザー1の表示
        $response->assertSee('ユーザー1'); //名前
        $response->assertSee('09:00'); // 出勤
        $response->assertSee('18:00'); // 退勤
        $response->assertSee('01:00'); // 休憩
        $response->assertSee('08:00'); // 合計

        // ユーザー2の表示
        $response->assertSee('ユーザー2'); //名前
        $response->assertSee('09:30'); // 出勤
        $response->assertSee('19:30'); // 退勤
        $response->assertSee('01:00'); // 休憩
        $response->assertSee('09:00'); // 合計
    }

    public function test_遷移した際に現在の日付が表示される()
    {
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        $dt = Carbon::now();
        $expectedDate = $dt->format('Y/m/d');
        $response->assertSee($expectedDate);
    }

    public function test_「前日」を押下した時に前の日の勤怠情報が表示される()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-19 10:00:00',
            'clock_out' => '2026-05-19 19:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-19 12:00:00',
            'break_end' => '2026-05-19 13:00:00',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));
        
        // 「前日」を押した際の遷移
        $response = $this->get('/admin/attendance/list?month=2026-05-19');
        $response->assertStatus(200);

        $response->assertSee('2026/05/19'); // 前日の表示
    }

    public function test_「翌日」を押下した時に次の日の勤怠情報が表示される()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-21 09:00:00',
            'clock_out' => '2026-05-21 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-21 12:00:00',
            'break_end' => '2026-05-21 13:00:00',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));
        
        // 「翌日」を押した際の遷移
        $response = $this->get('/admin/attendance/list?month=2026-05-21');
        $response->assertStatus(200);

        $response->assertSee('2026/05/21'); // 翌日の表示
    }
}
