<?php

// use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

//一般ユーザー：コントローラー
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;

//管理者：コントローラー
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;

//勤怠一覧：コントローラー
use App\Http\Controllers\AttendanceListController;

//勤怠詳細：コントローラー
use App\Http\Controllers\AttendanceDetailController;

//申請処理：コントローラー
use App\Http\Controllers\StampCorrectionRequestController;

//認証処理：コントローラー
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Requests\EmailVerificationRequest;
// use Illuminate\Foundation\Auth\EmailVerificationRequest;


//一般ユーザー：ログイン・会員登録
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

//メール認証関連
//メール未認証のユーザーに「/email/verify」へ誘導
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

// 認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    session()->get('unauthenticated_user')->sendEmailVerificationNotification();
    // $request->user()->sendEmailVerificationNotification();
    session()->put('resent', true);
    return back()->with('message', 'Verification link sent!');
})->name('verification.send');

// 認証メールのリンククリック処理
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    session()->forget('unauthenticated_user');
    return redirect('/attendance');
})->name('verification.verify');

//一般ユーザー：ログイン後
Route::middleware(['auth', 'verified'])->group(function () {

    //出勤登録画面（一般ユーザー）
    Route::get('/attendance', [UserAttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');
    Route::post('/attendance/break-start', [UserAttendanceController::class, 'breakStart'])
        ->name('attendance.breakStart');
    Route::post('/attendance/break-end', [UserAttendanceController::class, 'breakEnd'])
        ->name('attendance.breakEnd');

    //勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list', [AttendanceListController::class, 'list'])->name('attendance.list');

    //勤怠詳細画面（一般ユーザー）
    Route::get('/attendance/detail/{attendance}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/create', [AttendanceDetailController::class, 'create'])->name('attendance.create');
});



//管理者：ログイン前
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('/login', [LoginController::class, 'store']);
});

//管理者：ログイン後
Route::middleware('auth:admin')
->prefix('admin')
->group(function () {

    //勤怠一覧画面（管理者）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.index');

    //勤怠詳細画面（管理者）
    Route::get('/attendance/{attendance}', [AttendanceDetailController::class, 'show'])->name('admin.attendance_detail');
    Route::patch('/attendance/update', [AttendanceDetailController::class, 'update'])->name('admin.attendance_update');

    //スタッフ一覧画面（管理者）
    Route::get('/staff/list', [AdminAttendanceController::class, 'staff'])->name('admin.staff_list');

    //スタッフ別勤怠一覧画面（管理者）
    Route::get('/attendance/staff/{user}', [AttendanceListController::class, 'list_admin'])->name('admin.staff_attendance');
    Route::get('/attendance/staff/{user}/csv', [AttendanceListController::class, 'exportCsv'])->name('admin.staff_attendance_csv');

    //ログアウト（管理者）
    Route::post('/logout', function () {
        Auth::guard('admin')->logout();
        return redirect('/admin/login');
    });
});



//修正申請承認画面（管理者）
Route::middleware('auth:admin')
->group(function () {
    Route::get('stamp_correction_request/approve/{stampCorrectionRequest}', [StampCorrectionRequestController::class, 'show'])->name('admin.request_approve');
    Route::patch('stamp_correction_request/approve', [StampCorrectionRequestController::class, 'approve'])->name('admin.approved');
});

//一般ユーザー・管理者共通
//申請一覧画面（一般ユーザー）・申請一覧画面（管理者）
Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'list'])->name('stamp_correction_request.list')
    ->middleware('auth.user_or_admin');
