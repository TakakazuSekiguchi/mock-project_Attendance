@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_list.css') }}">
@endsection

@section('content')
<div class="content">
    <h1 class="page_title">スタッフ一覧</h1>
    <table class="staff__table">
        <tr class="staff__table-list">
            <th class="staff-list-th">名前</th>
            <th class="staff-list-th">メールアドレス</th>
            <th class="staff-list-th">月次勤怠</th>
        </tr>
        @foreach($users as $user)
        <tr class="staff__table-list">
            <td class="staff-list-th">{{ $user->name }}</td>
            <td class="staff-list-th">{{ $user->email }}</td>
            <td class="staff-list-th">
                <a href="{{ route('admin.staff_attendance', $user->id) }}">
                詳細
                </a>
            </td>
        </tr>
        @endforeach
    </table>
    
</div>
@endsection('content')
