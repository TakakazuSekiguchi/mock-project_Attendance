@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="content">
    <h1 class="page_title">勤怠詳細</h1>
    @auth('admin')
        <form class="form" action="{{ route('admin.attendance_update') }}" method="post">
        @method('PATCH')
    @elseauth('web')
        <form class="form" action="{{ route('attendance.create') }}" method="post">
    @endauth
        @csrf
        <div class="form__group">
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">名前</span>
                </div>
                <div class="form__group-content">
                    @auth('admin')
                        <span class="form__text-name">{{ $attendance->user->name ?? '' }}</span>
                    @elseauth('web')
                        <span class="form__text-name">{{ $user->name ?? '' }}</span>
                    @endauth
                    <input type="hidden" name="attendance_id" value="{{ $attendance?->id }}">
                </div>
            </div>
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">日付</span>
                </div>
                <div class="form__group-content">
                    <div class="form__input-text">
                        <span class="form__text-left">{{ $year."年" }}</span>
                        <span class="form__text-right">{{ $month."月".$day."日" }}</span>
                        <input type="hidden" name="target_date" value="{{ $target_date }}">
                    </div>
                </div>
            </div>
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">出勤・退勤</span>
                </div>
                @if($stampCorrectionRequest)
                <div class="form__group-content">
                    <div class="form__input-text">
                        <span class="form__text">{{ $pendingApproval_clock_in }}</span>
                        <span>～</span>
                        <span class="form__text">{{ $pendingApproval_clock_out }}</span>
                    </div>
                </div>
                @else
                <div class="form__group-content">
                    <div class="form__input-text">
                        <input class="form__input-left" type="text" name="clock_in"
                                value="{{ $attendance?->clock_in?->format('H:i') ?? '' }}"/>
                        <span>～</span>
                        <input class="form__input-right" type="text" name="clock_out"
                                value="{{ $attendance?->clock_out?->format('H:i') ?? '' }}"/>
                        @error('clock_in')
                        <div class="form__error">
                            {{ $errors->first('clock_in') }}
                        </div>
                        @enderror
                        @error('clock_out')
                        <div class="form__error">
                            {{ $errors->first('clock_out') }}
                        </div>
                        @enderror
                    </div>
                </div>
                @endif
            </div>
            @php
                $count = 0;
            @endphp
            @if($stampCorrectionRequest)
                @foreach($pendingApproval_breaks as $pendingApproval_break)
                @php
                    $count++;
                @endphp
                <div class="form__row">
                    <div class="form__group-title">
                        <span class="form__label-item">休憩{{ $count }}</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <span class="form__text">{{ $pendingApproval_break['pendingApproval_break_start'] }}</span>
                            <span>～</span>
                            <span class="form__text">{{ $pendingApproval_break['pendingApproval_break_end'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                @if($attendance)
                @foreach($attendance->breakTimes as $index => $break)
                @php
                    $count++;
                @endphp
                <div class="form__row">
                    <div class="form__group-title">
                        <span class="form__label-item">休憩{{ $count }}</span>
                    </div>
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <input class="form__input-left" type="text" name="breaks[{{ $index }}][start]"
                                value="{{ $break->break_start?->format('H:i') ?? '' }}"/>
                            <span>～</span>
                            <input class="form__input-right" type="text" name="breaks[{{ $index }}][end]"
                                value="{{ $break->break_end?->format('H:i') ?? '' }}"/>
                            @error("breaks.$index.start")
                            <div class="form__error">
                                {{ $errors->first("breaks.$index.start") }}
                            </div>
                            @enderror
                            @error("breaks.$index.end")
                            <div class="form__error">
                                {{ $errors->first("breaks.$index.end") }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
                <div class="form__row">
                    <div class="form__group-title">
                        @if($count >= 1)
                            <span class="form__label-item">休憩{{ $count + 1 }}</span>
                        @else
                            <span class="form__label-item">休憩</span>
                        @endif
                    </div>
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <input class="form__input-left" type="text" name="break_start" value=""/>
                            <span>～</span>
                            <input class="form__input-right" type="text" name="break_end" value=""/>
                            @error('break_start')
                            <div class="form__error">
                                {{ $errors->first('break_start') }}
                            </div>
                            @enderror
                            @error('break_end')
                            <div class="form__error">
                                {{ $errors->first('break_end') }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endif
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">備考</span>
                </div>
                @if($stampCorrectionRequest)
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <div class="form__text">{{ $stampCorrectionRequest->reason }}</div>
                        </div>
                    </div>
                @else
                    <div class="form__group-content">
                        <div class="form__input-text">
                            <textarea class="reason_textarea" name="reason"></textarea>
                        </div>
                        @error('reason')
                        <div class="form__error">
                            {{ $errors->first('reason') }}
                        </div>
                        @enderror
                    </div>
                @endif
            </div>
        </div>
        @if($stampCorrectionRequest)
            <div class="pendingApproval__parent">
                <p class="pendingApproval">※承認待ちのため修正はできません。</p>
            </div>
        @else
            <div class="form__button">
                <button class="form__button-submit" type="submit">修正</button>
            </div>
        @endif
    </form>
</div>
@endsection('content')
