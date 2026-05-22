<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\BreakRequestDetail;
use Carbon\Carbon;

class AttendanceDetailEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
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

        // 出勤時刻が退勤時刻より後になるように修正
        $response = $this->post(route('attendance.create', $attendance), [
            'clock_in' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('clock_in');
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
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

        // 休憩開始時刻が退勤時刻より後になるように修正
        $response = $this->post(route('attendance.create', $attendance), [
            'break_start' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_start');
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
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

        // 休憩終了時刻が退勤時刻より後になるように修正
        $response = $this->post(route('attendance.create', $attendance), [
            'break_end' => '2026-05-20 19:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_end');
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
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

        // 備考欄を未入力のまま保存
        $response = $this->post(route('attendance.create', $attendance), [
            'reason' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reason');
    }

    public function test_修正申請処理が実行される()
    {
        $user = User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        // $breakTime = BreakTime::factory()->create([
        //     'attendance_id' => $attendance->id,
        //     'break_start' => '2026-05-20 12:00:00',
        //     'break_end' => '2026-05-20 13:00:00',
        // ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance));

        // 勤怠詳細を修正＆申請
        // DBを直接更新しているのではなく、
        // ルート → Controller の処理を通して修正申請を実行している
        // []内の値は Request として Controller に渡される
        $response = $this->post(
            route('attendance.create', $attendance->id),
            [
                'attendance_id' => $attendance->id,
                'clock_in' => '10:00',
                'clock_out' => '18:00',
                'reason' => '電車遅延のため',
            ]
        );

        $response->assertRedirect();
        // $response->assertSessionHasNoErrors();

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance->id,
                'reason' => '電車遅延のため',
            ]
        );

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();

        // 管理者ログイン
        $this->actingAs($adminuser, 'admin');

        // 管理者ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        $stampCorrectionRequest = StampCorrectionRequest::factory()->create([
            'after_clock_in' => '2026-05-20 10:00:00',
            'after_clock_out' => '2026-05-20 18:00:00',
            'reason' => '電車遅延のため',
        ]);

        // 修正申請承認画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('admin.request_approve', $stampCorrectionRequest));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('電車遅延のため');

        // 申請一覧画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('stamp_correction_request.list'));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('電車遅延のため');
    }

}
