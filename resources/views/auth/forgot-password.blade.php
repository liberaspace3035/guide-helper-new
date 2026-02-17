@extends('layouts.app')

@section('content')
<div class="login-container" x-data="{ 
    email: '', 
    error: '', 
    loading: false,
    success: false
}">
    <div class="login-card">
        <div class="login-header">
            <h1>パスワードを忘れた方</h1>
            <p class="login-subtitle">メールアドレスを入力してください</p>
        </div>
        
        <div x-show="success" class="success-message" role="alert" aria-live="polite" x-transition>
            <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            パスワードリセットリンクを送信しました。メールをご確認ください。
        </div>
        
        <form method="POST" action="{{ route('password.email') }}" @submit.prevent="loading = true; $el.submit()" aria-label="パスワードリセットフォーム" x-show="!success">
            @csrf
            <div x-show="error" class="error-message" id="forgot-password-error-summary" role="alert" aria-live="polite" aria-atomic="true" x-text="error" x-transition></div>
            @if($errors->any())
                <div class="error-message" id="forgot-password-error-summary" role="alert" aria-live="polite" aria-atomic="true">
                    <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="sr-only">入力内容に誤りがあります。</span>{{ $errors->first() }}
                </div>
            @endif
            
            
            <div class="form-group">
                <label for="email">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    メールアドレス
                </label>
                <div class="input-wrapper">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        x-model="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        aria-required="true"
                        placeholder="example@email.com"
                        class="input-with-icon"
                    />
                </div>
            </div>
            
            <button
                type="submit"
                class="btn-primary btn-block btn-login"
                :disabled="loading"
                aria-label="リセットリンク送信ボタン"
            >
                <span x-show="!loading" class="btn-content">
                    <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    リセットリンクを送信
                </span>
                <span x-show="loading" class="btn-content" x-cloak>
                    <svg class="btn-icon spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                    </svg>
                    送信中...
                </span>
            </button>
        </form>
        
        <div class="login-footer">
            <p class="register-link">
                <a href="{{ route('login') }}">ログイン画面に戻る</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Login.css') }}">
<style>
.success-message {
    background-color: #e8f5e9;
    color: #2e7d32;
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
    border-left: 4px solid #2e7d32;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.success-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}
</style>
@endpush

