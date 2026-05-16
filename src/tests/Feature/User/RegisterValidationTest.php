<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_名前が未入力の場合にバリデーションエラーになる()
    {        
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // dd($response->getStatusCode());

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name']);
    }

    public function test_メールアドレスが未入力の場合にバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_パスワードが8文字未満の場合にバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_パスワードが確認用パスワードと一致しない場合にバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_パスワードが未入力の場合にバリデーションエラーになる()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }
}
