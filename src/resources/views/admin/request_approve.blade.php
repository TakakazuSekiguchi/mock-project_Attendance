@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="content">
    <h1 class="page_title">勤怠詳細</h1>
        <form class="form" action="{{ route('admin.approved') }}" method="post">
        @method('PATCH')
        @csrf
        <div class="form__group">
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">名前</span>
                </div>
                <div class="form__group-content">
                    <span class="form__text-name">{{ $stampCorrectionRequest->attendance->user->name ?? '' }}</span>
                    <input type="hidden" name="stampCorrectionRequest_id" value="{{ $stampCorrectionRequest?->id }}">
                </div>
            </div>
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">日付</span>
                </div>
                <div class="form__group-content">
                    <span class="form__text-left">{{ $year."年" }}</span>
                    <span class="form__text-right">{{ $month."月".$day."日" }}</span>
                </div>
            </div>
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">出勤・退勤</span>
                </div>
                <div class="form__group-content">
                    <span class="form__text-first">{{ $pendingApproval_clock_in }}</span>
                    <span>～</span>
                    <span class="form__text-last">{{ $pendingApproval_clock_out }}</span>
                </div>
            </div>
            @php
                $count = 0;
            @endphp
            @foreach($pendingApproval_breaks as $pendingApproval_break)
            @php
                $count++;
            @endphp
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">休憩{{ $count }}</span>
                </div>
                <div class="form__group-content">
                    <span class="form__text-first">{{ $pendingApproval_break['pendingApproval_break_start'] }}</span>
                    <span>～</span>
                    <span class="form__text-last">{{ $pendingApproval_break['pendingApproval_break_end'] }}</span>
                </div>
            </div>
            @endforeach
            <div class="form__row">
                <div class="form__group-title">
                    <span class="form__label-item">備考</span>
                </div>
                <div class="form__group-content">
                    <div class="form__text">{{ $stampCorrectionRequest->reason }}</div>
                </div>
            </div>
        </div>
        @if($stampCorrectionRequest->status === 1)
            <div class="form__button">
                <button class="form__button-submit-approved" type="submit">承認済み</button>
            </div>
        @else
            <div class="form__button">
                <button class="form__button-submit" type="submit">承認</button>
            </div>
        @endif
    </form>
</div>
@endsection('content')
