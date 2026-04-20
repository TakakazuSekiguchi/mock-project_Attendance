@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="content">
    @auth('admin')
        <h1 class="page_title">{{ $user->name .'の勤怠' }}</h1>
    @endauth
    @auth('web')
        <h1 class="page_title">勤怠一覧</h1>
    @endauth
    <div class="month_select-group">
        <a class="month_select-link" href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}">
            <span class="month_select-arrow">←</span>
            <span class="month_select">前月</span>
        </a>
        <div>
            <span class="thisMonth">{{ $month }}</span>
        </div>
        <a class="month_select-link" href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}">
            <span class="month_select">翌月</span>
            <span class="month_select-arrow">→</span>
        </a>
    </div>
    <table class="attendance__table">
        <tr class="attendance__table-list">
            <th class="attendance__table-list-th">日付</th>
            <th class="attendance__table-list-th">出勤</th>
            <th class="attendance__table-list-th">退勤</th>
            <th class="attendance__table-list-th">休憩</th>
            <th class="attendance__table-list-th">合計</th>
            <th class="attendance__table-list-th">詳細</th>
        </tr>
        @foreach($dates as $date)
            @php
                $work_date = $date->format('Y-m-d');
                $week = $date->isoFormat('ddd');

                $attendance = $attendances->first(function ($attendance) use ($work_date){
                    return $attendance->clock_in->format('Y-m-d') === $work_date;
                });

                $totalBreak = 0;
                $totalTime = 0;
                if($attendance) {
                    foreach($attendance->breakTimes as $break){
                        if ($break->break_start && $break->break_end){
                            $totalBreak += $break->break_end->diffInMinutes($break->break_start);
                        }
                    }

                    if(!is_null($attendance->clock_out)){
                        $totalTime = $attendance->clock_out->diffInMinutes($attendance->clock_in) - $totalBreak;
                    }
                }
            @endphp
            <tr class="attendance__table-list">
                <td>{{ $date->format('m/d')."(".$week.")" }}</td>
                <td>
                    {{ $attendance?->clock_in?->format('H:i') ?? '' }}
                </td>
                <td>
                    {{ $attendance?->clock_out?->format('H:i') ?? '' }}
                <td/> 
                <td>
                    {{ $attendance ? sprintf('%02d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '' }}
                </td>
                <td>
                    {{ $attendance ? sprintf('%02d:%02d', floor($totalTime / 60), $totalTime % 60) : '' }}
                </td>
                @auth('admin')
                    @if($attendance)
                        <td>
                            <a href="{{ route('admin.attendance_detail', $attendance->id) }}">詳細</a>
                        </td>
                    @else
                        <td>詳細</td>
                    @endif
                @elseauth('web')
                    @if($attendance)
                        <td>
                            <a href="{{ route('attendance.detail', $attendance->id) }}">詳細</a>
                        </td>
                    @else
                        <td>詳細</td>
                    @endif
                @endauth
            </tr>
        @endforeach
    </table>
    @auth('admin')
        <div class="csv__button">
            <button class="csv__button-submit" type="submit">CSV出力</button>
        </div>
    @endauth
</div>
@endsection('content')
