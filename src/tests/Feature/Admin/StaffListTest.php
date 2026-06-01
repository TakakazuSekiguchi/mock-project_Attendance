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

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる()
    {
        // ユーザーデータを作成（ユーザー1）
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'email' => 'test1@example.com',
        ]);

        // ユーザーデータを作成（ユーザー2）
        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
            'email' => 'test2@example.com',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();

        // 管理者ログイン
        $this->actingAs($adminuser, 'admin');

        // 管理者ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // スタッフ一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.staff_list'));

        $response->assertStatus(200);

        $response->assertSee('ユーザー1'); //ユーザー1の名前
        $response->assertSee('test1@example.com'); //ユーザー1のメールアドレス
        $response->assertSee('ユーザー2'); //ユーザー2の名前
        $response->assertSee('test2@example.com'); //ユーザー2のメールアドレス
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        // ユーザーデータを作成（ユーザー1）
        // スタッフ一覧画面（管理者）で選択される方
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

        // dd(Attendance::all());

        // ユーザーデータを作成（ユーザー2）
        // スタッフ一覧画面（管理者）で選択されない方
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

        // dd(BreakTime::all());

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // スタッフ一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.staff_list'));

        // ユーザー1の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.staff_attendance', $user1));

        $response->assertStatus(200);

        // 選択したユーザー1の表示
        $response->assertSee('ユーザー1の勤怠');
        $response->assertSee('05/20');
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        // ユーザーidを10に指定
        $user = User::factory()->create([
            'id' => 10,
        ]);

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));
        
        // 2026年5月を表示中に「前月」を押す想定
        $response = $this->get('/admin/attendance/staff/10?month=2026-04');
        $response->assertStatus(200);

        $response->assertSee('2026/04'); // 前月の表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_「翌月」を押下した時に表示月の翌月の情報が表示される()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        // ユーザーidを10に指定
        $user = User::factory()->create([
            'id' => 10,
        ]);

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

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));
        
        // 2026年5月を表示中に「翌月」を押す想定
        $response = $this->get('/admin/attendance/staff/10?month=2026-06');
        $response->assertStatus(200);

        $response->assertSee('2026/06'); // 前月の表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('1:00'); // 休憩時間
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        // 現在時刻を固定
        Carbon::setTestNow(
            Carbon::create(2026, 5, 20, 20, 30, 0)
        );

        $user = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        // 勤怠データを作成（1つ目の勤怠データ）
        // 勤怠一覧画面（管理者）で選択される方
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-18 09:00:00',
            'clock_out' => '2026-05-18 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-05-18 12:00:00',
            'break_end' => '2026-05-18 13:00:00',
        ]);

        // 勤怠データを作成（2つ目の勤怠データ）
        // 勤怠一覧画面（管理者）で選択されない方
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-05-19 09:30:00',
            'clock_out' => '2026-05-19 19:30:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-05-19 13:00:00',
            'break_end' => '2026-05-19 14:00:00',
        ]);

        // 管理者ユーザーを作成
        $adminuser = Admin::factory()->create();
        $this->actingAs($adminuser, 'admin');
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）に遷移
        $response = $this->from('/admin/login')
            ->get(route('admin.index'));

        // 5月18日の詳細から勤怠詳細画面（管理者）に遷移
        $response = $this->from(route('admin.index'))
            ->get(route('admin.attendance_detail', $attendance1));

        $response->assertStatus(200);

        // 選択したユーザー1の表示
        $response->assertSee('ユーザー1'); //名前
        $response->assertSee('2026年'); // 年表示
        $response->assertSee('5月18日'); // 月表示
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('12:00'); // 休憩入時刻
        $response->assertSee('13:00'); // 休憩戻時刻
    }
}
