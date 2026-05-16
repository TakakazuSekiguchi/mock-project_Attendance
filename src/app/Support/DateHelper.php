<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateHelper
{
    public static function displayDate(Request $request)
    {
        // 表示中の日 or 現在（今日）
        $dt = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::now();

        // requestがmonthだった場合
        // 表示中の月 or 現在（今月）
        if($request->filled('month')){
            $dt = $request->filled('month')
                ? Carbon::parse($request->input('month'))
                : Carbon::now();
        }

        $date = $dt->copy();
        return $date;
    }

    // public static function targetDate(Carbon $dt): array
    // {
    //     return [
    //         'year' => $dt->year,
    //         'month' => $dt->month,
    //         'day' => $dt->day,
    //         'target_date' => $dt->format('Y-m-d'),
    //     ];
    // }
}