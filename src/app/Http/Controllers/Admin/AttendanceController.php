<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
// use App\Models\StampCorrectionRequest;
// use App\Models\BreakRequestDetail;
// use App\Http\Requests\AttendanceDetailRequest;
// use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\DateHelper;

class AttendanceController extends Controller
{
    //勤怠一覧画面（管理者）
    public function index(Request $request){

        // 表示中の日 or 現在（今日）
        // デフォルト表示：今日
        $displayDate = DateHelper::displayDate($request);

        //選択された日付の表示
        $year = $displayDate->year;
        $month = $displayDate->month;
        $day = $displayDate->day;
        $today = $year ."年". $month ."月". $day ."日の勤怠";

        // 日付選択で表示される日付
        $date = $displayDate->format('Y/m/d');

        //スタッフの今日の勤怠状況
        $start = $displayDate->copy()->startOfDay();
        $end   = $displayDate->copy()->endOfDay();
        $attendances = Attendance::with('user')
            ->with('breakTimes')
            ->whereBetween('clock_in', [$start, $end])
            ->get();

        return view('admin.attendance_list', compact('attendances', 'today', 'date'));
    }

    //スタッフ一覧画面（管理者）
    public function staff(){
        $users = User::all();
        return view('admin.staff_list', compact('users'));
    }
}
