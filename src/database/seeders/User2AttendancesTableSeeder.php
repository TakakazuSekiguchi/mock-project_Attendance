<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class User2AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $today = Carbon::now();

        // 直近3～4ヶ月分の勤怠テーブルを作成（月末に作成した場合は約4か月分が作成される）
        for ($i = 2; $i >= 0; $i--) {
            // 月末のずれをなくす為、subMonthsNoOverflow()を使用
            $target = $today->copy()->subMonthsNoOverflow($i);
            $startDate = $target->copy()->startOfMonth();

            // 今月の場合は昨日までのデータを取得
            if ($target->isSameMonth($today)) {
                $endDate = $today->copy()->subDay();
            } else {
                $endDate = $target->copy()->endOfMonth();
            }

            $user = User::where('email', 'user2@example.com')->firstOrFail();

            // 初めの平日を退勤漏れとする際のif条件文で使用
            $forgotClockOutCreated = false;

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

                // 土日スキップ
                if ($date->isWeekend()) {
                    continue;
                }

                // 出勤時間（その日の9〜10時）
                $clockIn = (clone $date)->setTime(rand(9, 10), rand(0, 59));

                // 退勤（+8〜9時間）
                $clockOut = (clone $clockIn)->addHours(rand(8, 9));

                // 休憩（出勤から2〜3時間後）
                $breakStart = (clone $clockIn)->addHours(rand(2, 3));

                if ($breakStart->gte($clockOut)) {
                    $breakStart = (clone $clockOut)->subMinutes(90);
                }

                // 休憩終了（60分）
                $breakEnd = (clone $breakStart)->addMinutes(60);

                // 退勤前に収める
                if ($breakEnd->gt($clockOut)) {
                    $breakEnd = (clone $clockOut)->subMinutes(30);
                }

                // 前月のみ「退勤漏れ」を作成
                if ($i === 1){
                    // 最初の平日のみ退勤漏れ
                    if (!$forgotClockOutCreated) {
                        $attendance = Attendance::create([
                            'user_id' => $user->id,
                            'clock_in' => $clockIn,
                            'clock_out' => null,
                        ]);

                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $breakStart,
                            'break_end' => $breakEnd,
                        ]);

                        $forgotClockOutCreated = true;
                        continue;
                    }
                }

                // 以降の平日は正常な勤怠
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                ]);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);
            }
        }
    }
}
