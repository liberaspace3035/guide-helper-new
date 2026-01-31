@extends('layouts.app')

@section('content')
<div class="home-container">
    <!-- ヒーローセクション -->
    <section class="hero-section">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="hero-logo" />
        @guest
            <a href="{{ route('login') }}" class="hero-login-btn">
                ログイン
            </a>
        @endguest
        <div class="hero-content">
            <p class="hero-subtitle">
                <span class="hero-subtitle-line">視覚障害がある人の「一歩」と、社会が変わる「一歩」が重なり合う場所。</span>
                <br /><br />
                <span class="hero-subtitle-line">同行援護や居宅介護サービスの提供を中心に、外出・生活・仕事を、単なる「支援」ではなく、挑戦者が活躍し続けるための土台づくりとして支えるサービスです。</span>
                <br /><br />
                <span class="hero-subtitle-line">一般社団法人With Blindが運営しています。</span>
            </p>
            <div class="hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary btn-large">
                        ダッシュボードへ
                    </a>
                @else
                    <button onclick="window.location.href='{{ route('register') }}'" class="btn-primary btn-large">
                        今すぐ始める
                    </button>
                @endauth
            </div>
        </div>
    </section>

    <!-- 特徴セクション -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">サービスの特徴</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 class="feature-title">簡単マッチング</h3>
                    <p class="feature-description">
                        必要な情報を入力するだけで、最適なガイドヘルパーとマッチングできます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3 class="feature-title">リアルタイムチャット</h3>
                    <p class="feature-description">
                        マッチング後は、アプリ内チャットで直接コミュニケーションが取れます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3 class="feature-title">詳細レポート</h3>
                    <p class="feature-description">
                        ガイドヘルパーが活動内容をレポートとして記録し、安心して利用できます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3 class="feature-title">安全・安心</h3>
                    <p class="feature-description">
                        認証されたガイドヘルパーとマッチングし、安全にサービスを利用できます。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 使い方セクション -->
    <section class="how-it-works-section">
        <div class="container">
            <h2 class="section-title">使い方</h2>
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3 class="step-title">アカウント登録</h3>
                    <p class="step-description">
                        メールアドレスと基本情報を入力してアカウントを作成します。
                    </p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3 class="step-title">リクエスト作成</h3>
                    <p class="step-description">
                        日時、場所、必要なサポート内容を指定してリクエストを作成します。
                    </p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3 class="step-title">マッチング</h3>
                    <p class="step-description">
                        ガイドヘルパーがリクエストに応募し、マッチングが成立します。
                    </p>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <h3 class="step-title">活動開始</h3>
                    <p class="step-description">
                        チャットで詳細を確認し、当日ガイドヘルパーと活動を行います。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTAセクション -->
    @guest
        <section class="cta-section">
            <div class="container">
                <h2 class="cta-title">今すぐ始めましょう</h2>
                <p class="cta-description">
                    無料でアカウントを作成して、ガイドヘルパーサービスを利用できます
                </p>
                <div class="cta-actions">
                    <a href="{{ route('register') }}" class="btn-primary btn-large">
                        無料で登録
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary btn-large">
                        ログイン
                    </a>
                </div>
                <p class="cta-note">
                    新規会員登録後には審査と契約が必要です。審査と契約には1週間程度が必要です。
                </p>
            </div>
        </section>
    @endguest
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Home.css') }}">
@endpush
