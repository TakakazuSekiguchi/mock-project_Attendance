<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\BreakRequestDetail;
use Carbon\Carbon;

class StampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちの修正申請が全て表示されている()
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

        $stampCorrectionRequest1 = StampCorrectionRequest::where('reason', '電車遅延のため')->first();
        $this->assertNotNull($stampCorrectionRequest1);

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance1->id,
                'reason' => '電車遅延のため',
            ]
        );

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

        $stampCorrectionRequest2 = StampCorrectionRequest::where('reason', '打刻漏れのため')->first();
        $this->assertNotNull($stampCorrectionRequest2);

        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance2->id,
                'reason' => '打刻漏れのため',
            ]
        );

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();

        // 管理者ログイン
        $this->actingAs($adminuser, 'admin');

        // 管理者ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 申請一覧画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('stamp_correction_request.list'));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('承認待ち');
        $approvalResponse->assertSee('電車遅延のため');
        $approvalResponse->assertSee('打刻漏れのため');
    }

    public function test_承認済みの修正申請が全て表示されている()
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

        $stampCorrectionRequest1 = StampCorrectionRequest::where('reason', '電車遅延のため')->first();
        $this->assertNotNull($stampCorrectionRequest1);

        // DBに修正内容が登録されているか確認
        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance1->id,
                'reason' => '電車遅延のため',
            ]
        );

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

        $stampCorrectionRequest2 = StampCorrectionRequest::where('reason', '打刻漏れのため')->first();
        $this->assertNotNull($stampCorrectionRequest2);

        $this->assertDatabaseHas(
            'stamp_correction_requests',
            [
                'attendance_id' => $attendance2->id,
                'reason' => '打刻漏れのため',
            ]
        );

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

    public function test_修正申請の詳細内容が正しく表示されている()
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

        $stampCorrectionRequest = StampCorrectionRequest::where('reason', '電車遅延のため')->first();
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
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 申請一覧画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('stamp_correction_request.list'));

        // 修正申請承認画面（管理者）に遷移
        $approvalResponse = $this->from(route('stamp_correction_request.list'))
            ->get(route('admin.request_approve', $stampCorrectionRequest));

        $approvalResponse->assertStatus(200);
        $approvalResponse->assertSee('2026年'); // 年表示
        $approvalResponse->assertSee('5月20日'); // 月表示
        $approvalResponse->assertSee('10:00'); // 出勤時刻
        $approvalResponse->assertSee('18:00'); // 退勤時刻
        $approvalResponse->assertSee('12:00'); // 休憩入時刻
        $approvalResponse->assertSee('13:00'); // 休憩戻時刻
        $approvalResponse->assertSee('電車遅延のため');
    }

    public function test_修正申請の承認処理が正しく行われる()
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

        $stampCorrectionRequest = StampCorrectionRequest::where('reason', '電車遅延のため')->first();
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
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 修正申請承認画面（管理者）に遷移し、修正申請を承認
        $this->from('/admin/login')->patch(
            route('admin.approved', $stampCorrectionRequest->id),
            [
                'id' => $stampCorrectionRequest->id,
                'status' => 1,
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]
        );

        // 修正申請承認画面（管理者）に遷移
        $approvalResponse = $this->from('/admin/login')
            ->get(route('admin.request_approve', $stampCorrectionRequest));

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
