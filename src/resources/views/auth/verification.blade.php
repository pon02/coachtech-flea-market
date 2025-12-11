@extends('layouts.logo')
@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verification.css') }}">
@endsection
@section('content')
<div class="verification-form__content">
    <div class="verification-form__message">
        <p>登録していただいたメールアドレスに認証メールを送信しました。</p>
        <p>メール認証を完了してください。</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert-success">
            <p>新しい認証リンクをメールアドレスに送信しました。</p>
        </div>
    @endif
    <div class="verification-form__action">
        <a href="http://localhost:8025" target="_blank" class="verification-form__button">認証はこちらから</a>
    </div>
    <div class="verification-form__resend">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verification-form__resend-link">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection