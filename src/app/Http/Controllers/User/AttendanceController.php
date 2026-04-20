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
use App\Models\User;
use App\Models\StampCorrectionRequest;
use App\Models\BreakRequestDetail;
use App\Http\Requests\AttendanceDetailRequest;

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

        $attendance = Attendance::today(Auth::id())->first();

        return view('attendance', compact('attendance', 'today', 'time'));
    }

    public function clockIn(){
        $attendance = Attendance::today(Auth::id())->first();

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
        $attendance = Attendance::today(Auth::id())->first();

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
        $attendance = Attendance::today(Auth::id())->first();

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
        $attendance = Attendance::today(Auth::id())->first();

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

    //勤怠一覧
    public function list(Request $request){
        $user = auth()->user();

        // 表示中の月 or 現在
        $dt = $request->filled('month')
            ? Carbon::parse($request->input('month'))
            : Carbon::now();

        // デフォルト表示：今月
        $targetMonth = $dt->copy();

        // 表示用
        $month = $targetMonth->format('Y-m');

        // 範囲
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth   = $targetMonth->copy()->endOfMonth();

        // 日付配列
        $dates = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[] = $date->copy();
        }

        // 勤怠
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('clock_in', [$startOfMonth, $endOfMonth])
            ->orderBy('clock_in', 'desc')
            ->get();

        $attendanceDates = [];
        foreach($dates as $date){
            $work_date = $date->format('Y-m-d');
            $week = $date->isoFormat('ddd');

            //出勤日があれば処理を行う
            $totalBreak = 0;
            $totalTime = 0;
            $checkClockOut = "success";
            foreach($attendances as $attendance){
                if($attendance->clock_in->format('Y-m-d') === $work_date){
                    foreach($attendance->breakTimes as $break){
                        //休憩入と休憩戻がある場合（休憩時間を算出可能）
                        if ($break->break_start && $break->break_end){
                            $totalBreak += $break->break_end->diffInMinutes($break->break_start);
                        }
                    }

                    //退勤が行われた場合（勤怠時間を算出可能）
                    if(!is_null($attendance->clock_out)){
                        $totalTime = $attendance->clock_out->diffInMinutes($attendance->clock_in) - $totalBreak;
                    }else{
                        $checkClockOut = "miss";
                    }

                    if($checkClockOut === "miss"){
                        $attendanceDates[] = [
                            'work_date' => $work_date,
                            'week' => $week,
                            'attendance_id' => $attendance->id,
                            'clock_in' => Carbon::parse($attendance->clock_in)->format('H:i'),
                            'clock_out' => null,
                            'totalTime' => $totalTime,
                            'totalBreak' => $totalBreak
                        ];
                    }else{
                        $attendanceDates[] = [
                            'work_date' => $work_date,
                            'week' => $week,
                            'attendance_id' => $attendance->id,
                            'clock_in' => Carbon::parse($attendance->clock_in)->format('H:i'),
                            'clock_out' => Carbon::parse($attendance->clock_out)->format('H:i'),
                            'totalTime' => $totalTime,
                            'totalBreak' => $totalBreak
                        ];
                    }
                    break;
                }
            }

            //日付が一致しない場合
            if($attendance->clock_in->format('Y-m-d') !== $work_date){
                $attendanceDates[] = [
                    'work_date' => $work_date,
                    'week' => $week,
                    'attendance_id' => null,
                    'clock_in' => null,
                    'clock_out' => null,
                    'totalTime' => null,
                    'totalBreak' => null
                ];
            }
        }
        //配列の中身を確認
        // dd(array_map(function ($attendanceDate) {
        //     return $attendanceDate;
        // }, $attendanceDates));

        return view('attendance_list', compact('month', 'attendanceDates'));
    }

    public function show(Attendance $attendance){
        $user = auth()->user();

        $attendance = Attendance::with('breakTimes')
            ->find($attendance->id);
        
        $stampCorrectionRequest = StampCorrectionRequest::where('status', 0)
            ->where('attendance_id', $attendance->id)
            ->with('breakRequestDetails')
            ->first();
        // dd($stampCorrectionRequest->reason);

        $dt = Carbon::parse($attendance->clock_in);        
        $targetWorkDate = $dt->copy();

        // $date = [];
        $year = $targetWorkDate->year;
        $month = $targetWorkDate->month;
        $day = $targetWorkDate->day;
        $target_date = $targetWorkDate->format('Y-m-d');
        // array_push($date, 'year', 'month', 'day');

        //申請済みの場合に表示される出勤時刻・退勤時刻
        $pendingApproval_clock_in = '';
        $pendingApproval_clock_out = '';
        $pendingApproval_breaks = [];
        if($stampCorrectionRequest){
            $pendingApproval_clock_in = Carbon::parse($stampCorrectionRequest->after_clock_in)->format('H:i');
            $pendingApproval_clock_out = Carbon::parse($stampCorrectionRequest->after_clock_out)->format('H:i');

            //申請中の休憩時刻（複数表示）
            foreach($stampCorrectionRequest->breakRequestDetails as $index => $breakRequestDetail){
                $pendingApproval_breaks[] = [
                    'pendingApproval_break_start' => Carbon::parse($breakRequestDetail->after_start)->format('H:i'),
                    'pendingApproval_break_end' => Carbon::parse($breakRequestDetail->after_end)->format('H:i')                
                ];
            }
        }
        // dd($pendingApproval_breaks);

        return view('attendance_detail', compact(
            'year', 
            'month', 
            'day', 
            'target_date', 
            'user', 
            'attendance', 
            'stampCorrectionRequest',
            'pendingApproval_clock_in',
            'pendingApproval_clock_out',
            'pendingApproval_breaks'
        ));
    }

    public function create(AttendanceDetailRequest $request){
        //そのIDの勤怠データがDBに存在するか確認
        $attendance = Attendance::find($request->attendance_id);

        $date = $attendance->clock_in->format('Y-m-d');

        $after_clock_in = null;
        $after_clock_out = null;
        if(!empty($request->clock_in)){
            $after_clock_in = Carbon::parse($date . ' ' . $request->clock_in);
        }
        if(!empty($request->clock_out)){
            $after_clock_out = Carbon::parse($date . ' ' . $request->clock_out);    
        }

        DB::transaction(function () use (
            $request,
            $attendance,
            $date,
            $after_clock_in,
            $after_clock_out,
        ) {
            $createdStampCorrectionRequest = StampCorrectionRequest::Create([
                'user_id' => auth()->id(),
                'status' => 0,
                'reason' => $request->reason,
                'attendance_id' => $attendance->id,
                'after_clock_in' => $after_clock_in,
                'after_clock_out' => $after_clock_out,
                'approved_by' => null,
                'approved_at' => null
            ]);

            //勤怠データが存在する場合の休憩時刻の処理
            //breaksがあればループする。配列がない場合はエラーになるので回避の為、空の配列を用意（処理はスキップ）
            foreach ($request->breaks ?? [] as $index => $breakInput) {
                $break = $attendance->breakTimes[$index] ?? null;

                //clock_inまたはtarget_dateをベースに日付を取得し、各時刻データをdatetimeとして保持できるよう変更
                $after_start = null;
                $after_end = null;
                if(!empty($breakInput['start'])){
                    $after_start = Carbon::parse($date . ' ' . $breakInput['start']);        
                }
                if(!empty($breakInput['end'])){
                    $after_end = Carbon::parse($date . ' ' . $breakInput['end']);        
                }

                BreakRequestDetail::create([
                    'stamp_correction_request_id' => $createdStampCorrectionRequest->id,
                    'break_time_id' => $break?->id,
                    'after_start' => $after_start,
                    'after_end' => $after_end,
                ]);
            }

            //勤怠詳細で入力した休憩時刻の処理
            if($request->break_start && $request->break_end) {
            // if($request->break_start && $after_end = $request->break_end){
                $after_start = Carbon::parse($date . ' ' . $request->break_start);
                $after_end = Carbon::parse($date . ' ' . $request->break_end);

                $createdBreakRequestDetail = BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $after_start,
                    'break_end' => $after_end
                ]);

                BreakRequestDetail::create([
                    'stamp_correction_request_id' => $createdStampCorrectionRequest->id,
                    'break_time_id' => $createdBreakRequestDetail->id,
                    'after_start' => $createdBreakRequestDetail->break_start,
                    'after_end' => $createdBreakRequestDetail->break_end,
                ]);
            }
        });

        // return back()->with('success', '修正申請を送信しました');
        return redirect()->route('attendance.detail', $attendance->id)
            ->with('success', '修正申請を送信しました');
    }
}
