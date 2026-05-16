<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Admin;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ログインでメールアドレスが未入力の場合にバリデーションエラーになる()
    {
        //fromで明示的にどこから遷移してきたのかを記述
        //バリデーションエラーでback()となるため、遷移元を明示的にしておく必要がある
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertGuest('admin');
    }

    public function test_管理者ログインでパスワードが未入力の場合にバリデーションエラーになる()
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'testadmin@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password',
        ]);

        $this->assertGuest('admin');
    }

    public function test_管理者ログインでfortifyの入力情報が間違っている場合は認証エラーが表示される()
    {
        // 正しいユーザーを作成
        $adminuser = Admin::factory()->create([
            'email' => 'testadmin@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 間違ったパスワードでログイン
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'testadmin@example.com',
            'password' => 'wrong-password',
        ]);

        // 認証エラーがemailに入る
        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);

        // ログイン画面に戻る
        $response->assertRedirect('/admin/login');

        // 未ログインであること
        $this->assertGuest('admin');
    }

    public function test_管理者ログインで正しい情報が入力された場合、ログイン処理が実行される()
    {
        // ユーザーを作成
        $adminuser = Admin::factory()->create([
            'email' => 'testadmin@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // ログイン実行
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'testadmin@example.com',
            'password' => 'correct-password',
        ]);

        // ログイン成功している
        $this->assertAuthenticatedAs($adminuser, 'admin');

        // 勤怠一覧画面（管理者）にリダイレクト
        $response->assertRedirect('/admin/attendance/list');
    }
}
