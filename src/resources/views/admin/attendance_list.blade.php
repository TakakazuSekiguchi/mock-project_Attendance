@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="content">
    <h1 class="page_title">{{ $today }}</h1>
    <div class="date_select-group">
        <a class="date_select-link" href="?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}">
            <span class="date_select-arrow">←</span>
            <span class="date_select">前日</span>
        </a>
        <div>
            <span class="thisDate">{{ $date }}</span>
        </div>
        <a class="month_select-link" href="?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}">
            <span class="date_select">翌日</span>
            <span class="date_select-arrow">→</span>
        </a>
    </div>
    <table class="attendance__table">
        <tr class="attendance__table-list">
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
        @foreach($attendances as $attendance)
            @php
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
                <td>{{ $attendance?->user->name }}</td>
                <td>
                    {{ $attendance?->clock_in?->format('H:i') ?? '' }}
                </td>
                <td>
                    {{ $attendance?->clock_out?->format('H:i') ?? '' }}
                </td> 
                <td>
                    {{ $attendance ? sprintf('%02d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '' }}
                </td>
                <td>
                    {{ $attendance ? sprintf('%02d:%02d', floor($totalTime / 60), $totalTime % 60) : '' }}
                </td>
                @if($attendance)
                    <td>
                        <a href="{{ route('admin.attendance_detail', $attendance->id) }}">詳細</a>
                    </td>
                @else
                    <td>詳細</td>
                @endif
            </tr>
        @endforeach
    </table>
    {{-- <a href="{{ route('admin.attendance_detail') }}">リンク確認：勤怠詳細</a> --}}
</div>
@endsection('content')
