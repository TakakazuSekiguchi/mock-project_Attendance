<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

//formRequest関連で使用する予定
use Laravel\Fortify\Contracts\RegisterResponse;
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

//ログイン後の分岐を管理者と一般ユーザーで分ける為
use Laravel\Fortify\Contracts\LoginResponse;
use App\Responses\LoginResponse as CustomLoginResponse;

//ログアウト後の遷移ページをログインページに変更する為
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Responses\LogoutResponse as CustomLogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
        $this->app->singleton(LogoutResponse::class,CustomLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //ユーザー登録
        Fortify::createUsersUsing(CreateNewUser::class);

        //会員登録
        Fortify::registerView(function () {
                return view('auth.register');
        });

        //ログイン画面
        Fortify::loginView(function () {
            //表示崩れ防止の為、すべての権限で一旦ログアウト
            Auth::guard('admin')->logout();
            Auth::guard('web')->logout();

            return view('auth.login');
        });
            
        //ログイン試行制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        //デフォルトのログイン機能にあるフォームリクエストを自作のものに代替するため、サービスコンテナにバインド
        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
    }
}
