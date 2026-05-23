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

        $breakTime = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-20 12:00:00',
            'break_end' => '2026-05-20 13:00:00',
        ]);

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
                'break_start' => '12:00',
                'break_end' => '13:00',
                'reason' => '電車遅延のため',
            ]
        );

        // コントローラー側で作成されたStampCorrectionRequestを変数に格納
        $stampCorrectionRequest = StampCorrectionRequest::where(
                'reason',
                '電車遅延のため'
            )->first();

        $this->assertNotNull($stampCorrectionRequest);

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

    public function test_「承認待ち」にログインユーザーが行った申請が全て表示されていること()
    {
        $user = User::factory()->create();

        // 勤怠データを作成（一つ目の修正申請）
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        $response1 = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance1));

        // 勤怠詳細を修正＆申請
        $response1 = $this->post(
            route('attendance.create', $attendance1->id),
            [
                'attendance_id' => $attendance1->id,
                'clock_in' => '10:00',
                'clock_out' => '18:00',
                'reason' => '電車遅延のため',
            ]
        );

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance1->id,
                'reason' => '電車遅延のため',
            ]
        );

        // 勤怠データを作成（二つ目の修正申請）
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-21 10:00:00',
            'clock_out' => '2026-05-21 18:00:00',
        ]);

        $breakTime2 = BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-05-21 12:00:00',
            'break_end' => '2026-05-21 13:00:00',
        ]);

        $response2 = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance2));

        // 勤怠詳細を修正＆申請
        $response2 = $this->post(
            route('attendance.create', $attendance2->id),
            [
                'attendance_id' => $attendance2->id,
                'clock_in' => '10:00',
                'clock_out' => '18:00',
                'break_start' => '12:30',
                'break_end' => '13:30',
                'reason' => '休憩時刻の修正',
            ]
        );

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance2->id,
                'reason' => '休憩時刻の修正',
            ]
        );

        // dd(BreakTime::all());
        $this->assertDatabaseHas(
            'break_request_details',
            [
                // 'stamp_correction_request_id' => $stampCorrectionRequest2->id,
                // 'break_time_id' => $breakTime2->id,
                'after_start' => '2026-05-21 12:30:00',
                'after_end' => '2026-05-21 13:30:00',
            ]
        );

        // 申請一覧画面（一般ユーザー）に遷移
        $approvalResponse = $this->actingAs($user)
            ->get(route('stamp_correction_request.list'));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('電車遅延のため');
        $approvalResponse->assertSee('休憩時刻の修正');
    }

    public function test_「承認済み」に管理者が承認した修正申請が全て表示されている()
    {
        // 勤怠データを作成（ユーザー1の修正申請）
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'clock_in' => '2026-05-20 09:00:00',
            'clock_out' => '2026-05-20 18:00:00',
        ]);

        $response1 = $this->actingAs($user1)
            ->get(route('attendance.detail', $attendance1));

        // 勤怠詳細を修正＆申請
        $response1 = $this->post(
            route('attendance.create', $attendance1->id),
            [
                'attendance_id' => $attendance1->id,
                'clock_in' => '10:00',
                'clock_out' => '18:00',
                'reason' => '電車遅延のため',
            ]
        );

        $response1->assertRedirect();
        // $response->assertSessionHasNoErrors();

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance1->id,
                'reason' => '電車遅延のため',
            ]
        );

        $stampCorrectionRequest1 = StampCorrectionRequest::factory()->create([
            'after_clock_in' => '2026-05-20 10:00:00',
            'after_clock_out' => '2026-05-20 18:00:00',
            'reason' => '電車遅延のため',
        ]);

        // 勤怠データを作成（ユーザー2の修正申請）
        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'clock_in' => '2026-05-22 09:30:00',
            'clock_out' => null,
        ]);

        $response2 = $this->actingAs($user2)
            ->get(route('attendance.detail', $attendance2));

        $response2 = $this->post(
            route('attendance.create', $attendance2->id),
            [
                'attendance_id' => $attendance2->id,
                'clock_in' => '09:30',
                'clock_out' => '19:00',
                'reason' => '打刻漏れのため',
            ]
        );

        $response2->assertRedirect();

        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance2->id,
                'reason' => '打刻漏れのため',
            ]
        );

        $stampCorrectionRequest2 = StampCorrectionRequest::factory()->create([
            'after_clock_in' => '2026-05-22 09:30:00',
            'after_clock_out' => '2026-05-22 19:00:00',
            'reason' => '打刻漏れのため',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();

        // 管理者ログイン
        $this->actingAs($adminuser, 'admin');

        // 管理者ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 修正申請承認画面（管理者）に遷移し、それぞれのユーザーの修正申請を承認
        $this->from('/admin/login')->patch(
            route('admin.approved', $stampCorrectionRequest1->id),
            [
                'id' => $stampCorrectionRequest1->id,
                'status' => 1,
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]
        );

        $this->from('/admin/login')->patch(
            route('admin.approved', $stampCorrectionRequest2->id),
            [
                'id' => $stampCorrectionRequest2->id,
                'status' => 1,
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]
        );

        // 申請一覧画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('stamp_correction_request.list'));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('承認済み');
        $approvalResponse->assertSee('電車遅延のため');
        $approvalResponse->assertSee('打刻漏れのため');
    }

    public function test_各申請の「詳細」を押下すると勤怠詳細画面に遷移する()
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

        // 勤怠詳細を修正＆申請
        $response = $this->post(
            route('attendance.create', $attendance->id),
            [
                'attendance_id' => $attendance->id,
                'clock_in' => '10:00',
                'clock_out' => '18:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'reason' => '電車遅延のため',
            ]
        );

        $response->assertRedirect();

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance->id,
                'reason' => '電車遅延のため',
            ]
        );

        $stampCorrectionRequest = StampCorrectionRequest::factory()->create([
            'after_clock_in' => '2026-05-20 10:00:00',
            'after_clock_out' => '2026-05-20 18:00:00',
            'reason' => '電車遅延のため',
        ]);

        // 申請一覧画面（一般ユーザー）に遷移
        $approvalResponse = $this->actingAs($user)
            ->get(route('stamp_correction_request.list'));

        // 勤怠詳細画面（一般ユーザー）に遷移
        $approvalResponse = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('2026年'); // 年表示
        $approvalResponse->assertSee('5月20日'); // 月表示
        $approvalResponse->assertSee('10:00'); // 出勤時刻
        $approvalResponse->assertSee('18:00'); // 退勤時刻
        $approvalResponse->assertSee('12:00'); // 休憩入時刻
        $approvalResponse->assertSee('13:00'); // 休憩戻時刻
        $approvalResponse->assertSee('電車遅延のため');
    }
}
