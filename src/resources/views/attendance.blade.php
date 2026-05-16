@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="status">
        @if (!$attendance || $attendance->status === 'before_work')
            <span class="attendance_status">勤務外</span>
        @elseif ($attendance->status === 'working')
            <span class="attendance_status">出勤中</span>
        @elseif ($attendance->status === 'on_break')
            <span class="attendance_status">休憩中</span>
        @elseif ($attendance->status === 'finished')
            <span class="attendance_status">退勤済</span>
        @endif
    </div>
    <div class="attendance_date">{{ $today }}</div>
    <div class="attendance_time">{{ $time }}</div>
    <div class="form__button">
        @if (!$attendance || $attendance->status === 'before_work')
            <form class="form" action="{{ route('attendance.clockIn') }}" method="post">
                @csrf
                <button class="form__button-clockIn"submit">出勤</button>
            </form>
        @elseif ($attendance->status === 'working')
            <form method="POST" action="{{ route('attendance.clockOut') }}">
                @csrf
                <button class="form__button-clockOut" type="submit">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.breakStart') }}">
                @csrf
                <button class="form__button-break" type="submit">休憩入</button>
            </form>
        @elseif ($attendance->status === 'on_break')
            <form method="POST" action="{{ route('attendance.breakEnd') }}">
                @csrf
                <button class="form__button-break" type="submit">休憩戻</button>
            </form>
        @elseif ($attendance->status === 'finished')
            <p>お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection('content')
