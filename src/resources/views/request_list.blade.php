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
                    <th class="stampCorrectionRequest__table-list-th">状態</th>
                    <th class="stampCorrectionRequest__table-list-th">名前</th>
                    <th class="stampCorrectionRequest__table-list-th">対象日時</th>
                    <th class="stampCorrectionRequest__table-list-th">申請理由</th>
                    <th class="stampCorrectionRequest__table-list-th">申請日時</th>
                    <th class="stampCorrectionRequest__table-list-th">詳細</th>
                </tr>
                @php
                    $i = 0;
                @endphp
                @foreach($pendingApproval_requests as $pendingApproval_request)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $pendingApproval_request->user->name }}</td>
                    <td>{{ $pendingApproval_dates[$i]['after_clock_in'] }}</td>
                    <td>{{ $pendingApproval_request->reason }}</td>
                    <td>{{ $pendingApproval_dates[$i]['created_at'] }}</td>
                    @auth('admin')
                        <td>
                            <a href="{{ route('admin.request_approve', $pendingApproval_request->id) }}">詳細</a>
                        </td>
                    @elseauth('web')
                        <td>
                            <a href="{{ route('attendance.detail', $pendingApproval_request->attendance) }}">詳細</a>
                        </td>
                    @endauth
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
                    <th class="stampCorrectionRequest__table-list-th">状態</th>
                    <th class="stampCorrectionRequest__table-list-th">名前</th>
                    <th class="stampCorrectionRequest__table-list-th">対象日時</th>
                    <th class="stampCorrectionRequest__table-list-th">申請理由</th>
                    <th class="stampCorrectionRequest__table-list-th">申請日時</th>
                    <th class="stampCorrectionRequest__table-list-th">詳細</th>
                </tr>
                @php
                    $i = 0;
                @endphp
                @foreach($approved_requests as $approved_request)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $approved_request->user->name }}</td>
                    <td>{{ $approved_dates[$i]['after_clock_in'] }}</td>
                    <td>{{ $approved_request->reason }}</td>
                    <td>{{ $approved_dates[$i]['created_at'] }}</td>
                    @auth('admin')
                        <td>
                            <a href="{{ route('admin.request_approve', $approved_request->id) }}">詳細</a>
                        </td>
                    @elseauth('web')
                        <td>
                            <a href="{{ route('attendance.detail', $approved_request->attendance) }}">詳細</a>
                        </td>
                    @endauth
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
