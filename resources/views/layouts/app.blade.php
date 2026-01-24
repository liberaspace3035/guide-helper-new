<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="視覚障害者とガイドヘルパーのマッチングアプリケーション">
    <title>ガイドマッチングアプリ</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    @vite(['resources/css/app.scss'])
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="layout" x-data="{ sidebarVisible: window.innerWidth > 768, isMobile: window.innerWidth <= 768 }" 
         @resize.window="isMobile = window.innerWidth <= 768; if (isMobile) sidebarVisible = false; else sidebarVisible = true">
        <template x-if="sidebarVisible && isMobile">
            <div class="sidebar-overlay" @click="sidebarVisible = false" aria-hidden="true"></div>
        </template>
        <aside class="sidebar" :class="{ 'sidebar-hidden': !sidebarVisible }" role="navigation" aria-label="メインナビゲーション">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-logo" aria-label="ダッシュボードへ戻る">
                    <div class="logo-icon-wrapper">
                        <img src="{{ asset('images/logo.png') }}" alt="ガイドマッチ" class="logo-icon" />
                    </div>
                    <span class="sidebar-logo-text">One Step</span>
                </a>
            </div>
            <nav class="nav">
                <ul class="nav-list">
                    <li>
                        <a href="{{ route('dashboard') }}" :aria-current="window.location.pathname === '/dashboard' ? 'page' : undefined">
                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <span>ダッシュボード</span>
                        </a>
                    </li>
                    @auth
                        @if(auth()->user()->isUser())
                            <li>
                                <a href="{{ route('requests.create') }}" :aria-current="window.location.pathname === '/requests/new' ? 'page' : undefined">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>依頼作成</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('requests.index') }}" :aria-current="window.location.pathname === '/requests' ? 'page' : undefined">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                    </svg>
                                    <span>依頼一覧</span>
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->isGuide())
                            <li>
                                <a href="{{ route('guide.requests.index') }}" :aria-current="window.location.pathname === '/guide/requests' ? 'page' : undefined">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                    </svg>
                                    <span>依頼一覧</span>
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <li>
                                <a href="{{ route('admin.dashboard') }}" :aria-current="window.location.pathname === '/admin' ? 'page' : undefined">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                                    </svg>
                                    <span>管理画面</span>
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('profile') }}" :aria-current="window.location.pathname === '/profile' ? 'page' : undefined">
                                <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>プロフィール</span>
                            </a>
                        </li>
                        <li class="nav-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-logout" aria-label="ログアウト">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    <span>ログアウト</span>
                                </button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </nav>
        </aside>
        <div class="main-wrapper">
            <header class="header" role="banner">
                <div class="header-content">
                    <h1 class="logo">
                        <button class="menu-toggle-btn" aria-label="メニューを開閉" @click="sidebarVisible = !sidebarVisible" :aria-expanded="sidebarVisible">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="3" y1="7" x2="21" y2="7" />
                                <line x1="3" y1="12" x2="21" y2="12" />
                                <line x1="3" y1="17" x2="21" y2="17" />
                            </svg>
                        </button>
                    </h1>
                    @auth
                        <div class="user-menu" x-data="headerMenu()" x-init="init()">
                            <button 
                                class="header-icon-btn" 
                                :class="{ 'has-notifications': unreadCount > 0 }"
                                @click="handleChatClick()"
                                aria-label="メッセージ" 
                                :title="unreadCount > 0 ? `未読メッセージ: ${unreadCount}件` : 'メッセージ'"
                            >
                                <svg class="header-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <template x-if="unreadCount > 0">
                                    <span class="notification-badge" :aria-label="`未読メッセージ ${unreadCount}件`" x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                                </template>
                            </button>
                            <a href="{{ route('profile') }}" class="user-info" aria-label="プロフィール">
                                <div class="user-avatar" aria-hidden="true">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <span class="user-name" aria-label="ログインユーザー: {{ auth()->user()->name }}">
                                    {{ auth()->user()->name }}さん
                                </span>
                            </a>
                        </div>
                    @endauth
                </div>
            </header>
            <main class="main-content" role="main">
                @yield('content')
            </main>
            <footer class="footer" role="contentinfo">
                <p>&copy; {{ date('Y') }} One Step</p>
            </footer>
        </div>
    </div>
    @stack('scripts')
    <script>
        function headerMenu() {
            return {
                unreadCount: 0,
                activeMatchingId: null,
                intervalId: null,
                init() {
                    this.fetchUnreadCount();
                    this.fetchActiveMatching();
                    // 30秒ごとに未読メッセージ数を更新
                    this.intervalId = setInterval(() => {
                        this.fetchUnreadCount();
                    }, 30000);
                    // チャットページを開いたときに未読数を更新
                    window.addEventListener('chat-opened', () => {
                        this.fetchUnreadCount();
                    });
                },
                async apiFetch(url, options = {}) {
                    const response = await fetch(url, {
                        ...options,
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(options.headers || {})
                        }
                    });
                    
                    if (response.status === 401) {
                        console.error('認証エラー:', url);
                        throw new Error('認証エラー');
                    }
                    
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: 'エラーが発生しました' }));
                        console.error('APIエラー:', url, errorData);
                        throw new Error(errorData.error || 'エラーが発生しました');
                    }
                    
                    return response.json();
                },
                async fetchUnreadCount() {
                    try {
                        const data = await this.apiFetch('/api/chat/unread-count');
                        this.unreadCount = data.unread_count || 0;
                    } catch (error) {
                        if (error.message !== '認証エラー') {
                            console.error('未読メッセージ数取得エラー:', error);
                        }
                    }
                },
                async fetchActiveMatching() {
                    try {
                        const data = await this.apiFetch('/api/matchings/my-matchings');
                        // アクティブなマッチング（matched または in_progress）を取得
                        const activeMatching = data.matchings?.find(
                            m => m.status === 'matched' || m.status === 'in_progress'
                        );
                        if (activeMatching) {
                            this.activeMatchingId = activeMatching.id;
                        }
                    } catch (error) {
                        if (error.message !== '認証エラー') {
                            console.error('アクティブマッチング取得エラー:', error);
                        }
                    }
                },
                handleChatClick() {
                    if (this.activeMatchingId) {
                        this.unreadCount = 0; // クリックしたら未読数をリセット
                        window.location.href = `/chat/${this.activeMatchingId}`;
                    } else {
                        alert('マッチングが確定していません。');
                    }
                }
            }
        }
    </script>
</body>
</html>

