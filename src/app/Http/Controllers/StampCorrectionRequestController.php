<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;

class StampCorrectionRequestController extends Controller
{
    //共通化
    protected function prepareIndexData(Request $request): array
    {        
        $pendingApproval_query = StampCorrectionRequest::query();
        $approved_query = StampCorrectionRequest::query();
        
        if (auth('admin')->check()) {
            // 管理者
            // 承認待ちの申請
            $pendingApproval_requests = $pendingApproval_query->with('user')
                ->with('attendance')
                ->where('status', 0)
                ->get();

            // 承認済みの申請
            $approved_requests = $approved_query->with('user')
                ->with('attendance')
                ->where('status', 1)
                ->get();

        } else {
            
            // 一般ユーザー
            // 承認待ちの申請
            $pendingApproval_requests = $pendingApproval_query->with('user')
                ->with('attendance')
                ->where('user_id', auth('web')->id())
                ->where('status', 0)
                ->get();

            // 承認済みの申請
            $approved_requests = $approved_query->with('user')
                ->with('attendance')
                ->where('user_id', auth('web')->id())
                ->where('status', 1)
                ->get();
        }

        $defaultTab = $request->query('tab', 'pendingApproval');

        return [$pendingApproval_requests, $approved_requests, $defaultTab];
    }

    public function list(Request $request){
        [$pendingApproval_requests, $approved_requests, $defaultTab] = $this->prepareIndexData($request);

        $pendingApproval_dates = [];
        foreach($pendingApproval_requests as $pendingApproval_request){
            $dt_clo = Carbon::parse($pendingApproval_request->after_clock_in);        
            $targetWorkDate_clo = $dt_clo->copy();
            $dt_cre = Carbon::parse($pendingApproval_request->created_at);        
            $targetWorkDate_cre = $dt_cre->copy();

            $pendingApproval_dates[] = [
                'after_clock_in' => $targetWorkDate_clo->format('Y-m-d'),
                'created_at' => $targetWorkDate_cre->format('Y-m-d')
            ];
        }

        $approved_dates = [];
        foreach($approved_requests as $approved_request){
            $dt_clo = Carbon::parse($approved_request->after_clock_in);        
            $targetWorkDate_clo = $dt_clo->copy();
            $dt_cre = Carbon::parse($approved_request->created_at);        
            $targetWorkDate_cre = $dt_cre->copy();

            $approved_dates[] = [
                'after_clock_in' => $targetWorkDate_clo->format('Y-m-d'),
                'created_at' => $targetWorkDate_cre->format('Y-m-d')
            ];
        }

        return view('request_list', compact('pendingApproval_requests', 'approved_requests', 'defaultTab', 'pendingApproval_dates', 'approved_dates'));
    } 

    public function list_approved(Request $request){
        [$pendingApproval_requests, $approved_requests, $defaultTab] = $this->prepareIndexData($request);
        return redirect()->route('stamp_correction_request.list', ['tab' => 'approved']);
    }

    public function show(StampCorrectionRequest $stampCorrectionRequest){    
        $stampCorrectionRequest = StampCorrectionRequest::with('breakRequestDetails')
            ->with('user')
            ->find($stampCorrectionRequest->id);
        
        $dt = Carbon::parse($stampCorrectionRequest->after_clock_in);        
        $targetWorkDate = $dt->copy();

        // $date = [];
        $year = $targetWorkDate->year;
        $month = $targetWorkDate->month;
        $day = $targetWorkDate->day;
        $target_date = $targetWorkDate->format('Y-m-d');
        // array_push($date, 'year', 'month', 'day');

        $pendingApproval_clock_in = '';
        $pendingApproval_clock_out = '';
        $pendingApproval_breaks = [];
        if($stampCorrectionRequest){
            $pendingApproval_clock_in = Carbon::parse($stampCorrectionRequest->after_clock_in)->format('H:i');
            $pendingApproval_clock_out = Carbon::parse($stampCorrectionRequest->after_clock_out)->format('H:i');

            foreach($stampCorrectionRequest->breakRequestDetails as $index => $breakRequestDetail){
                $pendingApproval_breaks[] = [
                    'pendingApproval_break_start' => Carbon::parse($breakRequestDetail->after_start)->format('H:i'),
                    'pendingApproval_break_end' => Carbon::parse($breakRequestDetail->after_end)->format('H:i')                
                ];
            }
        }

        return view('admin.request_approve', compact(
            'year', 
            'month', 
            'day', 
            'target_date', 
            'stampCorrectionRequest',
            'pendingApproval_clock_in',
            'pendingApproval_clock_out',
            'pendingApproval_breaks'
        ));
    }

    public function approve(Request $request){
        $stampCorrectionRequest = StampCorrectionRequest::with('breakRequestDetails')
            ->find($request->stampCorrectionRequest_id);

        $dt = Carbon::parse($stampCorrectionRequest->after_clock_in);
        $targetWorkDate = $dt->copy();
        $date = $targetWorkDate->format('Y-m-d');

        $after_clock_in = Carbon::parse($stampCorrectionRequest->after_clock_in);
        $after_clock_out = Carbon::parse($stampCorrectionRequest->after_clock_out);

        DB::transaction(function () use (
            $stampCorrectionRequest,
            $date,
            $after_clock_in,
            $after_clock_out,
        ) {
            $stampCorrectionRequest->update([
                'status' => 1,
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]);

            Attendance::find($stampCorrectionRequest->attendance_id)->update([
                'clock_in'=> $after_clock_in,
                'clock_out' => $after_clock_out
            ]);

            foreach ($stampCorrectionRequest->breakRequestDetails ?? [] as $index => $breakInput) {

                $after_start = Carbon::parse($breakInput['after_start']);        
                $after_end = Carbon::parse($breakInput['after_end']);   

                BreakTime::where('attendance_id', $stampCorrectionRequest->attendance_id)->update([
                    'break_start' => $after_start,
                    'break_end' => $after_end
                ]);
            }
        });

        return redirect()->route('admin.request_approve', $stampCorrectionRequest->id);
    }
}
