@extends('layouts.app')

@section('content')
<div class="home-container">
    <!-- ヒーローセクション -->
    <section class="hero-section" aria-label="メインセクション">
        <img src="{{ asset('images/logo.png') }}" alt="One Step ロゴ" class="hero-logo" />
        
        <!-- アニメーション背景グラデーション -->
        <div class="hero-gradient-bg" aria-hidden="true">
            <div class="hero-gradient-layer hero-gradient-1"></div>
            <div class="hero-gradient-layer hero-gradient-2"></div>
            <div class="hero-gradient-layer hero-gradient-3"></div>
        </div>
        
        <!-- アニメーションするドットパターン -->
        <div class="hero-dot-pattern" aria-hidden="true"></div>
        
        <!-- 光のエフェクト -->
        <div class="hero-glow-effect hero-glow-1" aria-hidden="true"></div>
        <div class="hero-glow-effect hero-glow-2" aria-hidden="true"></div>
        
        <!-- 波形SVG背景（2層レイヤー） -->
        <div class="hero-wave-bg" aria-hidden="true">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="wave-svg wave-layer-1">
                <path d="M0,60 Q300,20 600,60 T1200,60 L1200,120 L0,120 Z" fill="rgba(255, 255, 255, 0.1)" class="wave-path"/>
            </svg>
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="wave-svg wave-layer-2">
                <path d="M0,70 Q400,30 800,70 T1200,70 L1200,120 L0,120 Z" fill="rgba(255, 255, 255, 0.05)" class="wave-path"/>
            </svg>
        </div>
        
        <div class="hero-content">
            <div class="hero-text-area">
                <p class="hero-catchphrase">スマホでかんたん</p>
                <h1 class="hero-main-title">
                    <span class="hero-keyword">同行援護</span>
                </h1>
                <p class="hero-subtitle">
                    視覚障害がある方の『行きたい』を、専門のガイドがサポート。One Stepならもっと気軽に、もっと便利に。
                </p>
                <div class="hero-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary btn-hero-primary" aria-label="ダッシュボードへ移動">
                            ダッシュボードへ
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-primary btn-hero-primary" aria-label="無料で登録">
                            無料で登録
                        </a>
                        <a href="{{ route('login') }}" class="btn-secondary btn-hero-secondary" aria-label="ログインページへ">
                            ログイン
                        </a>
                    @endauth
                </div>
            </div>
            <div class="hero-visual-area" aria-hidden="true">
                <!-- ビジュアル背景の抽象的な形状 -->
                <div class="hero-visual-bg">
                    <svg viewBox="0 0 400 400" class="hero-decorative-svg">
                        <circle cx="200" cy="200" r="150" fill="#EBF8FF" opacity="0.3"/>
                        <circle cx="100" cy="100" r="60" fill="#DBEAFE" opacity="0.4"/>
                        <circle cx="300" cy="300" r="80" fill="#DBEAFE" opacity="0.3"/>
                    </svg>
                </div>
                <!-- メインビジュアル: 視覚障害者とガイドのイラスト -->
                <div class="hero-main-visual hero-floating">
                    <svg viewBox="0 0 300 300" class="hero-illustration">
                        <!-- ガイド -->
                        <circle cx="100" cy="120" r="30" fill="#2563eb" opacity="0.2"/>
                        <circle cx="100" cy="100" r="20" fill="#2563eb"/>
                        <rect x="85" y="120" width="30" height="60" rx="5" fill="#2563eb"/>
                        <!-- 視覚障害者 -->
                        <circle cx="200" cy="120" r="30" fill="#2563eb" opacity="0.2"/>
                        <circle cx="200" cy="100" r="20" fill="#2563eb"/>
                        <rect x="185" y="120" width="30" height="60" rx="5" fill="#2563eb"/>
                        <!-- つながり -->
                        <path d="M 130 130 Q 150 110 170 130" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
                        <!-- スマホ -->
                        <rect x="180" y="140" width="20" height="35" rx="3" fill="#1e293b"/>
                        <rect x="183" y="145" width="14" height="20" fill="#ffffff"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- 特徴セクション -->
    <section class="features-section" aria-label="サービスの特徴">
        <!-- 波形SVGデコレーション -->
        <div class="features-wave-bg" aria-hidden="true">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="wave-svg">
                <path d="M0,0 Q300,40 600,0 T1200,0 L1200,120 L0,120 Z" fill="#f9fafb" class="wave-path"/>
            </svg>
        </div>
        <div class="container">
            <h2 class="section-title">サービスの特徴</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <svg class="feature-icon-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#icon-matching') }}"></use>
                        </svg>
                    </div>
                    <h3 class="feature-title">簡単マッチング</h3>
                    <p class="feature-description">
                        必要な情報を入力するだけで、最適なガイドヘルパーとマッチングできます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <svg class="feature-icon-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#icon-chat') }}"></use>
                        </svg>
                    </div>
                    <h3 class="feature-title">リアルタイムチャット</h3>
                    <p class="feature-description">
                        マッチング後は、アプリ内チャットで直接コミュニケーションが取れます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <svg class="feature-icon-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#icon-report') }}"></use>
                        </svg>
                    </div>
                    <h3 class="feature-title">詳細レポート</h3>
                    <p class="feature-description">
                        ガイドヘルパーが活動内容をレポートとして記録し、安心して利用できます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <svg class="feature-icon-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#icon-shield') }}"></use>
                        </svg>
                    </div>
                    <h3 class="feature-title">安全・安心</h3>
                    <p class="feature-description">
                        認証されたガイドヘルパーとマッチングし、安全にサービスを利用できます。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 使い方セクション -->
    <section class="how-it-works-section" aria-label="ご利用までの流れ">
        <!-- 波形SVGデコレーション -->
        <div class="steps-wave-bg" aria-hidden="true">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="wave-svg">
                <path d="M0,120 Q300,80 600,120 T1200,120 L1200,0 L0,0 Z" fill="#eff6ff" class="wave-path"/>
            </svg>
        </div>
        <div class="container">
            <h2 class="section-title">ご利用までの流れ</h2>
            <div class="steps-container">
                <div class="step-item">
                    <div class="step-number-wrapper">
                        <div class="step-number">1</div>
                        <svg class="step-number-bg" viewBox="0 0 100 100" aria-hidden="true">
                            <circle cx="50" cy="50" r="45" fill="#EBF8FF"/>
                        </svg>
                    </div>
                    <div class="step-illustration">
                        <svg class="step-illustration-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#illustration-register') }}"></use>
                        </svg>
                    </div>
                    <h3 class="step-title">アカウント登録</h3>
                    <p class="step-description">
                        メールアドレスと基本情報を入力してアカウントを作成します。
                    </p>
                    <div class="step-connector" aria-hidden="true">
                        <svg viewBox="0 0 100 20" class="step-arrow">
                            <path d="M 0 10 L 80 10 M 70 5 L 80 10 L 70 15" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number-wrapper">
                        <div class="step-number">2</div>
                        <svg class="step-number-bg" viewBox="0 0 100 100" aria-hidden="true">
                            <circle cx="50" cy="50" r="45" fill="#EBF8FF"/>
                        </svg>
                    </div>
                    <div class="step-illustration">
                        <svg class="step-illustration-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#illustration-request') }}"></use>
                        </svg>
                    </div>
                    <h3 class="step-title">リクエスト作成</h3>
                    <p class="step-description">
                        日時、場所、必要なサポート内容を指定してリクエストを作成します。
                    </p>
                    <div class="step-connector" aria-hidden="true">
                        <svg viewBox="0 0 100 20" class="step-arrow">
                            <path d="M 0 10 L 80 10 M 70 5 L 80 10 L 70 15" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number-wrapper">
                        <div class="step-number">3</div>
                        <svg class="step-number-bg" viewBox="0 0 100 100" aria-hidden="true">
                            <circle cx="50" cy="50" r="45" fill="#EBF8FF"/>
                        </svg>
                    </div>
                    <div class="step-illustration">
                        <svg class="step-illustration-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#illustration-matching') }}"></use>
                        </svg>
                    </div>
                    <h3 class="step-title">マッチング</h3>
                    <p class="step-description">
                        ガイドヘルパーがリクエストに応募し、マッチングが成立します。
                    </p>
                    <div class="step-connector" aria-hidden="true">
                        <svg viewBox="0 0 100 20" class="step-arrow">
                            <path d="M 0 10 L 80 10 M 70 5 L 80 10 L 70 15" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number-wrapper">
                        <div class="step-number">4</div>
                        <svg class="step-number-bg" viewBox="0 0 100 100" aria-hidden="true">
                            <circle cx="50" cy="50" r="45" fill="#EBF8FF"/>
                        </svg>
                    </div>
                    <div class="step-illustration">
                        <svg class="step-illustration-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#illustration-activity') }}"></use>
                        </svg>
                    </div>
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
        <section class="cta-section" aria-label="今すぐ始める">
            <div class="container">
                <h2 class="cta-title">今すぐ始めましょう</h2>
                <p class="cta-description">
                    無料でアカウントを作成して、ガイドヘルパーサービスを利用できます
                </p>
                <!-- マイクロコピー -->
                <div class="cta-microcopy">
                    <div class="cta-point">
                        <svg class="cta-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>登録3分</span>
                    </div>
                    <div class="cta-point">
                        <svg class="cta-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>月額0円</span>
                    </div>
                    <div class="cta-point">
                        <svg class="cta-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>安心の審査制</span>
                    </div>
                </div>
                <div class="cta-actions">
                    <a href="{{ route('register') }}" class="btn-cta-primary btn-large" aria-label="無料で登録">
                        無料で登録
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary btn-large" aria-label="ログインページへ">
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
