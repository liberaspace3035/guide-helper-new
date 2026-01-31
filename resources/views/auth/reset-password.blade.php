@extends('layouts.app')

@section('content')
<div class="login-container" x-data="{ 
    email: '{{ $email }}',
    password: '', 
    password_confirmation: '',
    showPassword: false,
    showPasswordConfirmation: false,
    error: '', 
    loading: false 
}">
    <div class="login-card">
        <div class="login-header">
            <h1>パスワードをリセット</h1>
            <p class="login-subtitle">新しいパスワードを入力してください</p>
        </div>
        
        <form method="POST" action="{{ route('password.update') }}" @submit.prevent="loading = true; $el.submit()" aria-label="パスワードリセットフォーム">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            
            <div x-show="error" class="error-message" role="alert" aria-live="polite" x-text="error" x-transition></div>
            @if($errors->any())
                <div class="error-message" role="alert" aria-live="polite">
                    <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    {{ $errors->first() }}
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
                        value="{{ $email }}"
                        required
                        autocomplete="email"
                        aria-required="true"
                        readonly
                        class="input-with-icon"
                        style="background-color: #f5f5f5;"
                    />
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    新しいパスワード
                </label>
                <div class="input-wrapper password-wrapper">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        name="password"
                        x-model="password"
                        required
                        autocomplete="new-password"
                        aria-required="true"
                        placeholder="新しいパスワードを入力"
                        class="input-with-icon"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="password-toggle"
                        :aria-label="showPassword ? 'パスワードを非表示' : 'パスワードを表示'"
                        tabindex="-1"
                    >
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" x-cloak>
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    パスワード（確認）
                </label>
                <div class="input-wrapper password-wrapper">
                    <input
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        id="password_confirmation"
                        name="password_confirmation"
                        x-model="password_confirmation"
                        required
                        autocomplete="new-password"
                        aria-required="true"
                        placeholder="パスワードを再入力"
                        class="input-with-icon"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                        class="password-toggle"
                        :aria-label="showPasswordConfirmation ? 'パスワードを非表示' : 'パスワードを表示'"
                        tabindex="-1"
                    >
                        <svg x-show="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg x-show="showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" x-cloak>
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button
                type="submit"
                class="btn-primary btn-block btn-login"
                :disabled="loading"
                aria-label="パスワードリセットボタン"
            >
                <span x-show="!loading" class="btn-content">
                    <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path>
                    </svg>
                    パスワードをリセット
                </span>
                <span x-show="loading" class="btn-content" x-cloak>
                    <svg class="btn-icon spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                    </svg>
                    リセット中...
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
@endpush

