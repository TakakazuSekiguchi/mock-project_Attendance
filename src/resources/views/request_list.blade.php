@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="content">
    <h1 class="page_title">申請一覧</h1>
    <div class="tab-switch">
        <input type="radio" id="tab1" name="TAB"
            {{ $defaultTab !== 'approved' ? 'checked' : '' }}>
        <label class="tab-boder" for="tab1">承認待ち</label>
        <div class="tab-content">
            <table class="stampCorrectionRequest__table">
                <tr class="stampCorrectionRequest__table-list">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @php
                    $i = 0;
                @endphp
                @foreach($pendingApproval_requests as $pendingApproval_request)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $pendingApproval_request->attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($pendingApproval_dates[$i]['after_clock_in'])->format('Y/m/d') }}</td>
                    <td>{{ $pendingApproval_request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($pendingApproval_dates[$i]['created_at'])->format('Y/m/d') }}</td>
                    <td>
                    @auth('admin')
                        <a href="{{ route('admin.request_approve', $pendingApproval_request->id) }}">詳細</a>
                    @elseauth('web')
                        <a href="{{ route('attendance.detail', $pendingApproval_request->attendance) }}">詳細</a>
                    @endauth
                    </td>
                </tr>
                @php
                    $i++;
                @endphp
                @endforeach
            </table>
        </div>
        <input type="radio" id="tab2" name="TAB"
            {{ $defaultTab === 'approved' ? 'checked' : '' }}>
        <label class="tab-boder" for="tab2">承認済み</label>
        <div class="tab-content">
            <table class="stampCorrectionRequest__table">
                <tr class="stampCorrectionRequest__table-list">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @php
                    $i = 0;
                @endphp
                @foreach($approved_requests as $approved_request)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $approved_request->attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($approved_dates[$i]['after_clock_in'])->format('Y/m/d') }}</td>
                    <td>{{ $approved_request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($approved_dates[$i]['created_at'])->format('Y/m/d') }}</td>
                    <td>
                    @auth('admin')
                        <a href="{{ route('admin.request_approve', $approved_request->id) }}">詳細</a>
                    @elseauth('web')
                        <a href="{{ route('attendance.detail', $approved_request->attendance) }}">詳細</a>
                    @endauth
                    </td>
                </tr>
                @php
                    $i++;
                @endphp
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection('content')
