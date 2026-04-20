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
use App\Models\StampCorrectionRequest;
use App\Models\BreakRequestDetail;
use App\Http\Requests\AttendanceDetailRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    //勤怠一覧画面（管理者）
    public function index(Request $request){
        // 表示中の日 or 現在（今日）
        $dt = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::now();

        // デフォルト表示：今日
        $targetdate = $dt->copy();
        // dd($targetdate);

        //選択された日付の表示
        $year = $targetdate->year;
        $month = $targetdate->month;
        $day = $targetdate->day;
        $today = $year ."年". $month ."月". $day ."日の勤怠";

        // 日付選択で表示される日付
        $date = $targetdate->format('Y/m/d');

        //スタッフの今日の勤怠状況
        $start = $targetdate->copy()->startOfDay();
        $end   = $targetdate->copy()->endOfDay();
        $attendances = Attendance::with('user')
            ->with('breakTimes')
            ->whereBetween('clock_in', [$start, $end])
            ->get();

        return view('admin.attendance_list', compact('attendances', 'today', 'date'));
    }

    //勤怠詳細画面（管理者）
    public function show(Attendance $attendance){
        $attendance = Attendance::with('user')
            ->with('breakTimes')
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
            'attendance', 
            'stampCorrectionRequest',
            'pendingApproval_clock_in',
            'pendingApproval_clock_out',
            'pendingApproval_breaks'
        ));
    }

    public function update(AttendanceDetailRequest $request){

        //そのIDの勤怠データがDBに存在するか確認
        $attendance = Attendance::find($request->attendance_id);

        //clock_inまたはtarget_dateをベースに日付を取得し、各時刻データをdatetimeとして保持できるよう変更
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
            Attendance::find($attendance->id)->update([
                'clock_in'=> $after_clock_in,
                'clock_out' => $after_clock_out
            ]);

            //勤怠データが存在する場合の休憩時刻の処理
            //breaksがあればループする。配列がない場合はエラーになるので回避の為、空の配列を用意（処理はスキップ）
            foreach ($request->breaks ?? [] as $index => $breakInput) {
                $break = $attendance->breakTimes[$index] ?? null;

                $after_start = null;
                $after_end = null;
                if(!empty($breakInput['start'])){
                    $after_start = Carbon::parse($date . ' ' . $breakInput['start']);        
                }
                if(!empty($breakInput['end'])){
                    $after_end = Carbon::parse($date . ' ' . $breakInput['end']);        
                }

                BreakTime::where('attendance_id', $attendance->id)->update([
                    'break_start' => $after_start,
                    'break_end' => $after_end
                ]);
            }

            //勤怠詳細で入力した休憩時刻の処理
            if($request->break_start && $request->break_end) {
                $after_start = Carbon::parse($date . ' ' . $request->break_start);
                $after_end = Carbon::parse($date . ' ' . $request->break_end);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $after_start,
                    'break_end' => $after_end
                ]);
            }
        });

        return redirect()->route('admin.attendance_detail', $attendance->id);
    }

    //スタッフ一覧画面（管理者）
    public function staff(){
        $users = User::all();
        return view('admin.staff_list', compact('users'));
    }

    //スタッフ別勤怠一覧画面（管理者）
    public function list(User $user, Request $request){
        $user = User::find($user->id);

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

        //配列の中身を確認
        // dd(array_map(function ($date) {
        //     return $date->format('Y-m-d');
        // }, $dates));

        // 勤怠
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('clock_in', [$startOfMonth, $endOfMonth])
            ->orderBy('clock_in', 'asc')
            ->get();
        // dd($attendances->toArray());

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

        return view('attendance_list', compact('month', 'attendanceDates', 'user'));
    }

    public function exportCsv(User $user, Request $request){
        $user = User::find($user->id);

        $month = $request->input('month', now()->format('Y-m'));

        // 月の開始・終了
        $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
        $endOfMonth = \Carbon\Carbon::parse($month)->endOfMonth();

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

        $fileName = $month . '.csv';

        //ヘッダーとCSVで抽出するデータを整える
        $csvData = [];
        $csvHeader = ['日付', '出勤', '退勤', '休憩', '合計'];

        foreach ($attendanceDates as $attendance) {
            $csvData[] = [
                $attendance['work_date'],
                $attendance['clock_in'],
                $attendance['clock_out'],
                $attendance['totalBreak'],
                $attendance['totalTime'],
            ];
        }

        //CSVに書き出す処理
        $response = new StreamedResponse(function () use ($csvHeader, $csvData) {
            $createCsvFile = fopen('php://output', 'w');

            // BOM付与（Excelでの文字化け防止）
            fwrite($createCsvFile, "\xEF\xBB\xBF");

            //ヘッダー出力
            fputcsv($createCsvFile, $csvHeader);

            //データ出力
            foreach ($csvData as $csv) {
                fputcsv($createCsvFile, $csv);
            }

            //CSVファイルをクローズ
            fclose($createCsvFile);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);

        return $response;

        // 備忘録
        // 無名関数として記載している部分が$callback
        // return new StreamedResponse($callback, 200, $headers);
    }
}
