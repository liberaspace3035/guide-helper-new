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
                    One Stepならもっと気軽に、もっと便利に。One Stepは、一般社団法人With Blindが運営する、視覚障害者向けの外出・自宅での生活支援サービスです。同行援護による外出支援に加え、居宅介護による自宅での生活支援も提供しています。
                    関東・関西を軸に、千葉・東京・神奈川・大阪・京都・神戸などを中心に全国展開を目指しています。
                </p>
                @auth
                    <div class="hero-actions">
                        <a href="{{ route('events.index') }}" class="btn-secondary btn-hero-secondary" aria-label="イベントカレンダーへ">
                            イベントカレンダー
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn-primary btn-hero-primary" aria-label="ダッシュボードへ移動">
                            ダッシュボードへ
                        </a>
                    </div>
                @else
                    <div class="hero-actions hero-actions--guest-grid">
                        <div class="hero-actions-col hero-actions-col--secondary" aria-label="カレンダーとログイン">
                            <a href="{{ route('events.index') }}" class="btn-secondary btn-hero-secondary" aria-label="イベントカレンダーへ">
                                イベントカレンダー
                            </a>
                            <a href="{{ route('login') }}" class="btn-secondary btn-hero-secondary" aria-label="ログインページへ">
                                ログイン
                            </a>
                        </div>
                        <div class="hero-actions-col hero-actions-col--primary" aria-label="新規登録">
                            <a href="{{ route('register', ['role' => 'user']) }}" class="btn-primary btn-hero-primary" aria-label="利用者として登録">
                                利用者として登録
                            </a>
                            <a href="{{ route('register', ['role' => 'guide']) }}" class="btn-primary btn-hero-primary" aria-label="ガイドとして登録">
                                ガイドとして登録
                            </a>
                        </div>
                    </div>
                @endauth
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

    @if(isset($publicNotices) && $publicNotices->isNotEmpty())
    <section class="container" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem 0;" aria-label="お知らせ">
        <h2 class="section-title" style="margin-bottom: 1rem;">お知らせ</h2>
        <div class="requests-list" style="display: grid; gap: 0.75rem;">
            @foreach($publicNotices as $n)
                <article class="request-card" style="padding: 1rem;">
                    <p style="font-size: 0.85rem; color: #64748b; margin: 0 0 0.35rem;">
                        {{ $n->published_at?->format('Y/m/d') }} · {{ $n->getCategoryLabel() }}
                    </p>
                    <h3 style="font-size: 1.05rem; margin: 0 0 0.35rem;">{{ $n->title }}</h3>
                    <p style="margin: 0; white-space: pre-wrap; font-size: 0.95rem;">{{ \Illuminate\Support\Str::limit($n->body, 200) }}</p>
                    @if($n->detail_url)
                        <p style="margin: 0.5rem 0 0;"><a href="{{ $n->detail_url }}" target="_blank" rel="noopener noreferrer">詳細リンク</a></p>
                    @endif
                </article>
            @endforeach
        </div>
        <p style="margin-top: 1rem;">
            <a href="{{ route('public-notices.index') }}" class="btn-secondary">お知らせ一覧へ</a>
        </p>
    </section>
    @endif

    <section class="container home-feature-intro" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem 0;" aria-label="このページでできること">
        <details class="home-details-card" style="border:1px solid #e2e8f0; border-radius:8px; padding:1rem 1.25rem; background:#fff;">
            <summary style="cursor:pointer; font-weight:700; font-size:1.05rem;">このページでできること（タップで開閉）</summary>
            <div style="margin-top:1rem; font-size:0.95rem; line-height:1.65;">
                <p style="font-weight:600; margin-bottom:0.35rem;">【利用者の方にできること】</p>
                <ul style="margin:0 0 1rem 1.1rem; padding:0;">
                    <li>ガイドに外出支援や自宅支援を依頼できます</li>
                    <li>希望するガイドを指名して依頼できます</li>
                    <li>イベントカレンダーから支援依頼につなげることができます</li>
                    <li>マイカレンダーに予定を登録し、予定に合わせて支援を依頼できます</li>
                    <li>ガイドの対応可能日時を確認できます</li>
                </ul>
                <p style="font-weight:600; margin-bottom:0.35rem;">【ガイドの方にできること】</p>
                <ul style="margin:0 0 1rem 1.1rem; padding:0;">
                    <li>利用者からの依頼に応募できます</li>
                    <li>利用者を指名して支援を提案できます</li>
                    <li>外出や自宅での支援内容を提案できます</li>
                    <li>イベントカレンダーを登録・閲覧できます</li>
                    <li>マイカレンダーで予定を管理できます</li>
                </ul>
                <p style="font-weight:600; margin-bottom:0.35rem;">【カレンダーについて】</p>
                <ul style="margin:0 0 0 1.1rem; padding:0;">
                    <li>イベントカレンダーは、どなたでも登録・閲覧できます。視覚障害に関するイベント情報を登録してください。</li>
                    <li>マイカレンダーは、ご本人のみ投稿・閲覧できます。ご自身のプライベートな予定の管理やガイド予定の管理で利用してください。</li>
                </ul>
            </div>
        </details>
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
                    <h3 class="feature-title">かんたんガイド確定</h3>
                    <p class="feature-description">
                        必要な情報を入力するだけで、最適なガイドヘルパーとガイド確定ができます。
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <svg class="feature-icon-svg" aria-hidden="true">
                            <use href="{{ asset('images/icons-sprite.svg#icon-chat') }}"></use>
                        </svg>
                    </div>
                    <h3 class="feature-title">リアルタイムメッセージ</h3>
                    <p class="feature-description">
                        ガイド確定後は、アプリ内メッセージで直接やり取りができます。
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
                        活動後の報告確認手続きをガイドと利用者が実施することで、利用者の利用時間とガイドの活動時間が確定します。
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
                        利用者・ガイドとも面談を実施し、要望を事前に確認したうえでご利用いただいています。ガイドの研修・教育にも力を入れています。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 利用までの流れ -->
    <section class="how-it-works-section" aria-label="利用までの流れ">
        <div class="steps-wave-bg" aria-hidden="true">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="wave-svg">
                <path d="M0,120 Q300,80 600,120 T1200,120 L1200,0 L0,0 Z" fill="#eff6ff" class="wave-path"/>
            </svg>
        </div>
        <div class="container">
            <h2 class="section-title">利用までの流れ</h2>
            <p class="how-it-works-intro">
                一般社団法人With Blindが運営する障害福祉サービスです。視覚障害当事者の視点を大切にしながら、制度の説明だけでなく、実際の生活の中でどう役立つかを丁寧にご案内します。
            </p>

            <h3 class="flow-subsection-title">【利用者の方】</h3>
            <ol class="home-flow-list" style="line-height:1.75; margin:0 0 1.5rem 1.25rem; padding:0;">
                <li style="margin-bottom:0.65rem;"><strong>アカウント登録</strong> — まずは本ページから利用者登録をします。</li>
                <li style="margin-bottom:0.65rem;"><strong>面談・ご説明</strong> — 運営よりご連絡し、サービス内容やご利用方法をご案内します。</li>
                <li style="margin-bottom:0.65rem;"><strong>利用契約・本登録</strong> — 必要なお手続き完了後、サービスをご利用いただけます。</li>
                <li style="margin-bottom:0.65rem;"><strong>依頼作成</strong> — 日時・場所・希望する支援内容を入力します。</li>
                <li style="margin-bottom:0.65rem;"><strong>ガイド確定</strong> — 条件に合うガイドヘルパーが決まります。</li>
                <li style="margin-bottom:0.65rem;"><strong>サービス利用</strong> — 当日は内容に沿って支援を受けられます。</li>
            </ol>

            <h3 class="flow-subsection-title">【ガイドの方】</h3>
            <ol class="home-flow-list" style="line-height:1.75; margin:0 0 0 1.25rem; padding:0;">
                <li style="margin-bottom:0.65rem;"><strong>アカウント登録</strong> — まずは本ページからガイド登録をします。</li>
                <li style="margin-bottom:0.65rem;"><strong>面談・資格確認</strong> — 運営よりご連絡し、資格や活動内容を確認します。</li>
                <li style="margin-bottom:0.65rem;"><strong>手続き・本登録</strong> — 必要なお手続き完了後、活動を開始できます。</li>
                <li style="margin-bottom:0.65rem;"><strong>依頼へ応募</strong> — 条件に合う依頼に応募できます。</li>
                <li style="margin-bottom:0.65rem;"><strong>ガイド確定</strong> — 利用者との条件が合えばガイドとして確定します。</li>
                <li style="margin-bottom:0.65rem;"><strong>支援実施</strong> — 当日は依頼内容に沿って支援を行います。</li>
            </ol>
        </div>
    </section>

    <!-- CTAセクション -->
    @guest
        <section class="cta-section" aria-label="今すぐ始める">
            <div class="container">
                <h2 class="cta-title">今すぐ始める</h2>
                <p class="cta-description">
                    無料でアカウントを作成し、視覚障害者向けの外出支援・自宅生活支援サービスをご利用いただけます。
                </p>
                <p class="cta-description" style="margin-top:0.75rem;">
                    利用者の方も、ガイドの方も、無料でアカウント登録ができます。<br>
                    ご利用や活動開始には、初回面談と必要なお手続きがあります。<br>
                    詳細は登録後に、一般社団法人With Blindよりご案内します。
                </p>
                <div class="cta-microcopy">
                    <div class="cta-point">
                        <svg class="cta-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>登録の目安: 約10〜15分</span>
                    </div>
                    <div class="cta-point">
                        <svg class="cta-check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>初回面談あり</span>
                    </div>
                </div>
                <div class="cta-actions" style="flex-direction:column; align-items:stretch; gap:0.75rem;">
                    <a href="{{ route('register', ['role' => 'user']) }}" class="btn-cta-primary btn-large" aria-label="利用者として登録">
                        利用者として登録
                    </a>
                    <a href="{{ route('register', ['role' => 'guide']) }}" class="btn-cta-primary btn-large" aria-label="ガイドとして登録">
                        ガイドとして登録
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary btn-large" aria-label="ログインページへ">
                        ログイン
                    </a>
                </div>
            </div>
        </section>
    @endguest
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Home.css') }}">
@endpush
