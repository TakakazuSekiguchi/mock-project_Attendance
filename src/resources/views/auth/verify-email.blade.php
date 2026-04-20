@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email__content">
    <div class="verify-email__heading">
        <p class="verify-email__text">登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p class="verify-email__text">メール認証を完了してください。</p>
    </div>
    <div class="form__button">
        <a class="form__button-submit" href="https://mailtrap.io/">認証はこちらから</a>
    </div>
    <form class="form" action="{{ route('verification.send') }}" method="post">
        @csrf
        <div class="form__link">
            <button class="form__link-submit" type="submit">認証メールを再送する</button>
        </div>
    </form>
</div>
@endsection