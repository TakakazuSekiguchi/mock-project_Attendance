<?php

namespace App\Http\Controllers;

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

class AttendanceDetailController extends Controller
{
    //共通化：勤怠詳細データ（配列）
    private function getAttendanceDetail(Attendance $attendance){
        $attendance = Attendance::with('user')
            ->with('breakTimes')
            ->find($attendance->id);
        
        $stampCorrectionRequest = StampCorrectionRequest::where('status', 0)
            ->where('attendance_id', $attendance->id)
            ->with('breakRequestDetails')
            ->first();
        // dd($stampCorrectionRequest);

        $dt = Carbon::parse($attendance->clock_in);
        $targetWorkDate = $dt->copy();

        $year = $targetWorkDate->year;
        $month = $targetWorkDate->month;
        $day = $targetWorkDate->day;
        $target_date = $targetWorkDate->format('Y-m-d');

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

        return [
            'year' => $year, 
            'month' => $month, 
            'day' => $day, 
            'target_date' => $target_date, 
            'attendance' => $attendance, 
            'stampCorrectionRequest' => $stampCorrectionRequest,
            'pendingApproval_clock_in' => $pendingApproval_clock_in,
            'pendingApproval_clock_out' => $pendingApproval_clock_out,
            'pendingApproval_breaks' => $pendingApproval_breaks
        ];
    }

    //勤怠詳細画面（一般ユーザー）
    public function show(Attendance $attendance){ 
        $data = $this->getAttendanceDetail($attendance);
        return view('attendance_detail', $data);
    }

    //勤怠詳細画面（一般ユーザー）：修正申請
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

    //勤怠詳細画面（管理者）
    public function show_admin(Attendance $attendance){
        $data = $this->getAttendanceDetail($attendance);
        return view('attendance_detail', $data);
    }

    //勤怠詳細画面（管理者）：勤怠修正
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
}
