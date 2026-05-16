<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\DateHelper;

class AttendanceListController extends Controller
{
    //共通化：勤怠データ（配列）
    private function getAttendanceDates($user, $startOfMonth, $endOfMonth){
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
            $attendance = $attendances->firstWhere('date', $date) ?? null;

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
            if($attendance?->clock_in->format('Y-m-d') !== $work_date){
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

        return [$attendanceDates];
    }

    //勤怠一覧（一般ユーザー）
    public function list(Request $request){
        $user = auth()->user();

        // 表示中の月 or 現在（今月）
        // デフォルト表示：今月
        $displayMonth = DateHelper::displayDate($request);

        // 表示用
        $month = $displayMonth->format('Y-m');

        // 範囲
        $startOfMonth = $displayMonth->copy()->startOfMonth();
        $endOfMonth   = $displayMonth->copy()->endOfMonth();
        [$attendanceDates] = $this->getAttendanceDates($user, $startOfMonth, $endOfMonth);

        return view('attendance_list', compact('month', 'attendanceDates'));
    }

    //スタッフ別勤怠一覧画面（管理者）
    public function list_admin(User $user, Request $request){
        $user = User::find($user->id);

        // 表示中の月 or 現在（今月）
        // デフォルト表示：今月
        $displayMonth = DateHelper::displayDate($request);

        // 表示用
        $month = $displayMonth->format('Y-m');

        // 範囲
        $startOfMonth = $displayMonth->copy()->startOfMonth();
        $endOfMonth   = $displayMonth->copy()->endOfMonth();
        [$attendanceDates] = $this->getAttendanceDates($user, $startOfMonth, $endOfMonth);

        return view('attendance_list', compact('month', 'attendanceDates', 'user'));
    }

    //CSV出力（管理者）
    public function exportCsv(User $user, Request $request){
        $user = User::find($user->id);

        $month = $request->input('month', now()->format('Y-m'));

        // 月の開始・終了
        $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
        $endOfMonth = \Carbon\Carbon::parse($month)->endOfMonth();
        [$attendanceDates] = $this->getAttendanceDates($user, $startOfMonth, $endOfMonth);

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
