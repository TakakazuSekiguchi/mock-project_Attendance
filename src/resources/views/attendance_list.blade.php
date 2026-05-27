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
            <img class="calendar__img" src="{{ asset('images/カレンダーアイコン8.jpeg') }}">
            <span class="thisMonth">{{ \Carbon\Carbon::parse($month)->format('Y/m') }}</span>
        </div>
        <a class="month_select-link" href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}">
            <span class="month_select">翌月</span>
            <span class="month_select-arrow">→</span>
        </a>
    </div>
    <table class="attendance__table">
        <tr class="attendance__table-list">
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
        @foreach($attendanceDates as $attendanceDate)
        <tr class="attendance__table-list">
            <td>
                {{ 
                    \Carbon\Carbon::parse($attendanceDate['work_date'])
                        ->format('m/d')."(".$attendanceDate['week'].")"
                 }}
            </td>
            <td>
                {{ 
                    $attendanceDate['attendance_id'] && $attendanceDate['clock_in']
                        ? \Carbon\Carbon::parse($attendanceDate['clock_in'])->format('H:i')
                        : ''
                }}
            </td>
            <td>
                {{ 
                    $attendanceDate['attendance_id'] && $attendanceDate['clock_out']
                        ? \Carbon\Carbon::parse($attendanceDate['clock_out'])->format('H:i')
                        : ''
                }}
            </td>
            <td>
                {{ $attendanceDate['attendance_id'] ? sprintf('%02d:%02d', floor($attendanceDate['totalBreak'] / 60), $attendanceDate['totalBreak'] % 60) : '' }}
            </td>
            <td>
                {{ $attendanceDate['attendance_id'] ? sprintf('%02d:%02d', floor($attendanceDate['totalTime'] / 60), $attendanceDate['totalTime'] % 60) : '' }}
            </td>
            <td>
            @if($attendanceDate['attendance_id'])
                @auth('admin')
                    <a href="{{ route('admin.attendance_detail', $attendanceDate['attendance_id']) }}">詳細</a>
                @elseauth('web')
                    <a href="{{ route('attendance.detail', $attendanceDate['attendance_id']) }}">詳細</a>
                @endauth
            @else
                詳細
            @endif
            </td>
        </tr>
        @endforeach
    </table>
    @auth('admin')
        <div class="csv__button">
            <a class="csv__button-submit" href="{{ route('admin.staff_attendance_csv', [
                'user' => $user,
                'month' => $month
            ]) }}">
                CSV出力
            </a>
        </div>
    @endauth
</div>
@endsection('content')
