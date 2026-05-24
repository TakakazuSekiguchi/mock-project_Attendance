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

class AttendanceDetailEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
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

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.attendance_detail', $attendance1));

        $response->assertStatus(200);

        // 選択したユーザー1の表示
        $response->assertSee('ユーザー1'); //名前
        $response->assertSee('2026年'); // 年表示
        $response->assertSee('5月20日'); // 月表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('12:00'); // 休憩入時刻
        $response->assertSee('13:00'); // 休憩戻時刻
    }

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        // 勤怠データを作成
        $user = User::factory()->create();

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.attendance_detail', $attendance));

        $response->assertStatus(200);

        // 出勤時刻が退勤時刻より後になるように修正
        $response = $this->patch(route('admin.attendance_update', $attendance), [
            'clock_in' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('clock_in');
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        // 勤怠データを作成
        $user = User::factory()->create();

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.attendance_detail', $attendance));

        $response->assertStatus(200);

        // 休憩開始時刻が退勤時刻より後になるように修正
        $response = $this->patch(route('admin.attendance_update', $attendance), [
            'break_start' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_start');
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        // 勤怠データを作成
        $user = User::factory()->create();

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.attendance_detail', $attendance));

        $response->assertStatus(200);

        // 休憩終了時刻が退勤時刻より後になるように修正
        $response = $this->patch(route('admin.attendance_update', $attendance), [
            'break_end' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_end');
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        // 勤怠データを作成
        $user = User::factory()->create();

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.attendance_detail', $attendance));

        $response->assertStatus(200);

        // 備考欄を未入力のまま保存
        $response = $this->patch(route('admin.attendance_update', $attendance), [
            'reason' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reason');
    }
}
