<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メールアドレスが未入力の場合にバリデーションエラーになる()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_パスワードが未入力の場合にバリデーションエラーになる()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_fortifyで入力情報が間違っている場合は認証エラーが表示される()
    {
        // 正しいユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 間違ったパスワードでログイン
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // 認証エラーがemailに入る
        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);

        // ログイン画面に戻る
        $response->assertRedirect();

        // 未ログインであること
        $this->assertGuest();
    }

    public function test_正しい情報が入力された場合、ログイン処理が実行される()
    {
        // ユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // ログイン実行
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        // ログイン成功している
        $this->assertAuthenticatedAs($user);

        // Fortifyのhomeにリダイレクト
        $response->assertRedirect(config('fortify.home'));
    }
}
