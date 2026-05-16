<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    //勤怠画面
    public function index(){
        $dt = Carbon::now();

        $year = $dt->year;
        $month = $dt->month;
        $day = $dt->day;
        $time = $dt->format('H:i');
        $week = $dt->isoFormat('ddd');

        $today = $year ."年". $month ."月". $day ."日(". $week .")";

        $attendance = Attendance::todayByUser(auth()->id())->first();

        return view('attendance', compact('attendance', 'today', 'time'));
    }

    public function clockIn(){
        $attendance = Attendance::todayByUser(auth()->id())->first();

        if ($attendance) {
            return back()->withErrors('すでに出勤済みです');
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'clock_in' => now(),
        ]);

        return redirect()->back();
    }

    public function clockOut(){
        $attendance = Attendance::todayByUser(auth()->id())->first();

        // 出勤してない場合
        if (!$attendance) {
            return back()->withErrors('出勤していません');
        }

        // すでに退勤済みチェック
        if ($attendance->clock_out) {
            return back()->withErrors('すでに退勤済みです');
        }

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->back();
    }

    public function breakStart(){
        $attendance = Attendance::todayByUser(auth()->id())->first();

        // 出勤してない場合
        if (!$attendance) {
            return back()->withErrors('出勤していません');
        }

        // 最新の休憩を取得
        $latestBreak = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        // すでに休憩中かチェック
        if ($latestBreak && !$latestBreak->break_end) {
            return back()->withErrors('すでに休憩中です');
        }

        // 休憩入の作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        return redirect()->back();
    }

    public function breakEnd(){
        $attendance = Attendance::todayByUser(auth()->id())->first();

        // 出勤してない場合
        if (!$attendance) {
            return back()->withErrors('出勤していません');
        }

        // 最新の休憩を取得
        $latestBreak = BreakTime::where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        // 休憩中でない
        if (!$latestBreak || $latestBreak->break_end) {
            return back()->withErrors('休憩中ではありません');
        }

        // 休憩終了
        $latestBreak->update([
            'break_end' => now(),
        ]);

        return redirect()->back();
    }
}
