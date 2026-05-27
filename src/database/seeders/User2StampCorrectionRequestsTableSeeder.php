<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\BreakRequestDetail;

class User2StampCorrectionRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user2 = User::where('email', 'user2@example.com')->first();

        // 勤怠修正
        $attendance1 = Attendance::where('user_id', $user2->id)
            ->whereNotNull('clock_out')
            ->inRandomOrder()
            ->first();

        $baseDate1 = Carbon::parse($attendance1->clock_in); //clock_inを基準に出勤日を取得
        $breakTime = $attendance1->breakTimes->first();
        $breakStart1 = Carbon::parse($breakTime->break_start);
        $breakEnd1 = Carbon::parse($breakTime->break_end);

        $afterClockIn = $baseDate1->copy()->setTime(10, 0);
        $afterClockOut = $baseDate1->copy()->setTime(19, 0);
        $afterStart = $breakStart1->copy();
        $afterEnd = $breakEnd1->copy();

        $request = StampCorrectionRequest::create([
            'status' => 0,
            'reason' => '勤怠修正テスト',
            'attendance_id' => $attendance1->id,
            'after_clock_in' => $afterClockIn,
            'after_clock_out' => $afterClockOut,
            'approved_by' => null,
            'approved_at' => null
        ]);

        BreakRequestDetail::create([
            'stamp_correction_request_id' => $request->id,
            'break_time_id' => $breakTime->id,
            'after_start' => $afterStart,
            'after_end' => $afterEnd,
        ]);


        // 休憩修正
        $attendance2 = Attendance::where('user_id', $user2->id)
            ->has('breakTimes')
            ->whereNotNull('clock_out')
            ->inRandomOrder()
            ->first();
        
        $baseDate2 = Carbon::parse($attendance2->clock_in);
        $clockOut2 = Carbon::parse($attendance2->clock_out);
        $breakTime = $attendance1->breakTimes->first();

        $afterClockIn = $baseDate2->copy();
        $afterClockOut = $clockOut2->copy();
        $afterStart = $baseDate2->copy()->setTime(12, 30);
        $afterEnd = $baseDate2->copy()->setTime(13, 30);

        $request = StampCorrectionRequest::create([
            'status' => 0,
            'reason' => '休憩修正テスト',
            'attendance_id' => $attendance2->id,
            'after_clock_in' => $afterClockIn,
            'after_clock_out' => $afterClockOut,
            'approved_by' => null,
            'approved_at' => null
        ]);

        BreakRequestDetail::create([
            'stamp_correction_request_id' => $request->id,
            'break_time_id' => $breakTime->id,
            'after_start' => $afterStart,
            'after_end' => $afterEnd,
        ]);


        // 退勤漏れ
        $attendance3 = Attendance::where('user_id', $user2->id)
            ->whereNull('clock_out')
            ->first();

        $baseDate3 = Carbon::parse($attendance3->clock_in);
        $breakTime = $attendance3->breakTimes->first();
        $breakStart3 = Carbon::parse($breakTime->break_start);
        $breakEnd3 = Carbon::parse($breakTime->break_end);

        $afterClockIn = $baseDate3->copy();
        $afterClockOut = $baseDate3->copy()->setTime(19, 0);
        $afterStart = $breakStart3->copy();
        $afterEnd = $breakEnd3->copy();

        $request = StampCorrectionRequest::create([
            'status' => 0,
            'reason' => '退勤漏れ修正テスト',
            'attendance_id' => $attendance3->id,
            'after_clock_in' => $afterClockIn,
            'after_clock_out' => $afterClockOut,
            'approved_by' => null,
            'approved_at' => null
        ]);

        BreakRequestDetail::create([
            'stamp_correction_request_id' => $request->id,
            'break_time_id' => $breakTime->id,
            'after_start' => $afterStart,
            'after_end' => $afterEnd,
        ]);
    }
}
