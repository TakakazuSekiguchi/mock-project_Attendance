<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

     public function test_会員登録後に認証メールが送信される()
    {
        //VerifyEmail は「メール（Mailable）」ではなく「通知（Notification）」
        //なのでMailではなくNotificationを使用
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    //メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    //PHPUnitテストでは外部サイトへの遷移ができない為、DB上に認証日時が記録されたことを確認することで代替
    public function test_メール認証を行うとDBに認証日時が記録される()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->unverified()->create();

        // 認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // $response = $this->actingAs($user)->get($verificationUrl);
        // dd(
        //     $response->status(),
        //     $response->exception,
        //     $response->getContent()
        // );

        //認証URLにアクセス
        $this->actingAs($user)->get($verificationUrl);

        // DBにメール認証日時が記録されている
        $this->assertNotNull(
            $user->refresh()->email_verified_at
        );
    }

    public function test_メール認証完了後にプロフィール設定画面へ遷移する()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->unverified()->create();

        // 認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // 認証URLへアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // 出勤登録画面へリダイレクトされる
        $response->assertRedirect('/attendance');

        // ユーザーが認証済みになっている
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
