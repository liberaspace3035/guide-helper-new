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
    <script>
        // グローバルなfetchヘルパー関数（419/401エラーハンドリング付き）
        window.handleApiResponse = async function(response) {
            // 419エラー（CSRFトークン期限切れ）: ページをリロードして新しいトークンを取得
            if (response.status === 419) {
                console.warn('セッション期限切れ（419）。ページを再読み込みします。');
                alert('セッションの期限が切れました。ページを再読み込みします。');
                window.location.reload();
                return false;
            }
            
            // 401エラー（認証エラー）: ログイン画面へリダイレクト
            if (response.status === 401) {
                console.error('認証エラー');
                alert('認証が期限切れです。ログイン画面に移動します。');
                window.location.href = '/login?message=expired';
                return false;
            }
            
            return true;
        };
        
        // グローバルなfetchラッパー関数
        window.apiFetch = async function(url, options = {}) {
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
            
            // 419/401エラーのハンドリング
            const shouldContinue = await window.handleApiResponse(response);
            if (!shouldContinue) {
                throw new Error('認証エラーまたはセッション期限切れ');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'エラーが発生しました' }));
                console.error('APIエラー:', url, errorData);
                throw new Error(errorData.error || 'エラーが発生しました');
            }
            
            return response.json();
        };
        
        // ログアウトフォームの419エラーハンドリング
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                logoutForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            credentials: 'include',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });
                        
                        // 419エラーのハンドリング（既存のhandleApiResponse関数を使用）
                        const shouldContinue = await window.handleApiResponse(response);
                        if (!shouldContinue) {
                            return; // handleApiResponseがページを再読み込みするため、ここで終了
                        }
                        
                        // 成功時はリダイレクト（サーバーからのリダイレクトに従う）
                        if (response.ok || response.redirected) {
                            window.location.href = '/';
                        } else {
                            // その他のエラー時は通常のフォーム送信にフォールバック
                            this.submit();
                        }
                    } catch (error) {
                        console.error('ログアウトエラー:', error);
                        // エラー時は通常のフォーム送信にフォールバック
                        this.submit();
                    }
                });
            }
        });
        
        function headerMenu() {
            return {
                unreadCount: 0,
                activeMatchingId: null,
                intervalId: null,
                consecutiveErrors: 0, // 連続エラーカウント
                pollingInterval: 30000, // ポーリング間隔（30秒）
                init() {
                    this.fetchUnreadCount();
                    this.fetchActiveMatching();
                    // 30秒ごとに未読メッセージ数を更新
                    this.startPolling();
                    // チャットページを開いたときに未読数を更新
                    window.addEventListener('chat-opened', () => {
                        this.fetchUnreadCount();
                    });
                    // ページの可視性変更を監視（非アクティブ時はポーリングを停止）
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.stopPolling();
                        } else {
                            this.startPolling();
                        }
                    });
                },
                startPolling() {
                    this.stopPolling();
                    this.intervalId = setInterval(() => {
                        if (!document.hidden) {
                            this.fetchUnreadCount();
                        }
                    }, this.pollingInterval);
                },
                stopPolling() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                },
                isNetworkError(error) {
                    // ネットワークエラーの判定
                    const errorMessage = error.message || error.toString();
                    const errorName = error.name || '';
                    
                    // AbortError（タイムアウト）もネットワークエラーとして扱う
                    if (errorName === 'AbortError' || errorMessage.includes('aborted')) {
                        return true;
                    }
                    
                    const networkErrorPatterns = [
                        'ERR_NETWORK_CHANGED',
                        'ERR_NAME_NOT_RESOLVED',
                        'Failed to fetch',
                        '取得に失敗しました',
                        'NetworkError',
                        'ネットワークエラー',
                        'Network request failed',
                        'ネットワークリクエストに失敗しました',
                        'TypeError: Failed to fetch',
                        'TypeError: 取得に失敗しました'
                    ];
                    
                    return networkErrorPatterns.some(pattern => 
                        errorMessage.includes(pattern)
                    );
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
                    
                    // 419エラー（CSRFトークン期限切れ）: ページをリロードして新しいトークンを取得
                    if (response.status === 419) {
                        console.warn('セッション期限切れ（419）。ページを再読み込みします。');
                        alert('セッションの期限が切れました。ページを再読み込みします。');
                        window.location.reload();
                        return;
                    }
                    
                    // 401エラー（認証エラー）: ログイン画面へリダイレクト
                    if (response.status === 401) {
                        console.error('認証エラー:', url);
                        alert('認証が期限切れです。ログイン画面に移動します。');
                        window.location.href = '/login?message=expired';
                        throw new Error('認証エラー');
                    }
                    
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: 'エラーが発生しました' }));
                        console.error('APIエラー:', url, errorData);
                        throw new Error(errorData.error || 'エラーが発生しました');
                    }
                    
                    return response.json();
                },
                async fetchUnreadCount(retryCount = 0) {
                    try {
                        // タイムアウト処理（AbortControllerを使用）
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒でタイムアウト
                        
                        const response = await fetch('/api/chat/unread-count', {
                            credentials: 'include',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            signal: controller.signal
                        });
                        
                        clearTimeout(timeoutId);
                        
                        // 419エラー（CSRFトークン期限切れ）: ページをリロード
                        if (response.status === 419) {
                            console.warn('セッション期限切れ（419）。ページを再読み込みします。');
                            window.location.reload();
                            return;
                        }
                        
                        // 401エラー（認証エラー）: エラーメッセージを表示せずに終了
                        if (response.status === 401) {
                            // 認証エラーは静かに処理（ログイン画面へのリダイレクトはapiFetchで処理）
                            return;
                        }
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.unreadCount = data.unread_count || 0;
                            this.consecutiveErrors = 0; // 成功時はエラーカウントをリセット
                        } else {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    } catch (error) {
                        // ネットワークエラーの判定
                        const isNetworkError = this.isNetworkError(error);
                        
                        if (isNetworkError) {
                            // ネットワークエラーの場合、リトライ（最大3回、指数バックオフ）
                            if (retryCount < 3) {
                                const delay = 1000 * Math.pow(2, retryCount); // 1秒、2秒、4秒
                                await new Promise(resolve => setTimeout(resolve, delay));
                                return this.fetchUnreadCount(retryCount + 1);
                            }
                            
                            // 連続エラーが発生した場合、ポーリング間隔を延長
                            this.consecutiveErrors++;
                            if (this.consecutiveErrors >= 5) {
                                // ポーリング間隔を延長（最大60秒）
                                const newInterval = Math.min(this.pollingInterval * 2, 60000);
                                this.pollingInterval = newInterval;
                                this.stopPolling();
                                this.startPolling();
                            }
                        } else if (error.message !== '認証エラー') {
                            // ネットワークエラー以外で、認証エラーでない場合のみログ出力
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
                            <li x-data="{ adminMenuOpen: window.location.pathname.startsWith('/admin') }">
                                <button @click="adminMenuOpen = !adminMenuOpen" class="nav-item-button" :class="{ active: window.location.pathname.startsWith('/admin') }">
                                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                                    </svg>
                                    <span>管理画面</span>
                                    <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'rotate-90': adminMenuOpen }">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                                <ul class="nav-submenu" x-show="adminMenuOpen" x-transition>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'dashboard' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'dashboard'; } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="7" height="7"></rect>
                                                <rect x="14" y="3" width="7" height="7"></rect>
                                                <rect x="14" y="14" width="7" height="7"></rect>
                                                <rect x="3" y="14" width="7" height="7"></rect>
                                            </svg>
                                            <span>ダッシュボード</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'users' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'users'; if (window.adminDashboard.users.length === 0) window.adminDashboard.fetchUsers(); } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                            <span>ユーザー管理</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'guides' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'guides'; if (window.adminDashboard.guides.length === 0) window.adminDashboard.fetchGuides(); } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <span>ガイド管理</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'announcements' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'announcements'; } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                            </svg>
                                            <span>お知らせ管理</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'monthly-limits' }" @click.prevent="if (window.adminDashboard) { if (window.adminDashboard.activeTab !== 'monthly-limits') { window.adminDashboard.activeTab = 'monthly-limits'; if (window.adminDashboard.users.length === 0) window.adminDashboard.fetchUsers(); else window.adminDashboard.fetchAllUserCurrentLimits(); } } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <span>限度時間管理</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'email-templates' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'email-templates'; if (window.adminDashboard.emailTemplates.length === 0) window.adminDashboard.fetchEmailTemplates(); } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                            <span>メールテンプレート</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'email-settings' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'email-settings'; if (window.adminDashboard.emailSettings.length === 0) window.adminDashboard.fetchEmailSettings(); } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                            </svg>
                                            <span>メール通知設定</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}" :class="{ active: window.location.pathname === '/admin' && window.adminDashboard && window.adminDashboard.activeTab === 'operation-logs' }" @click.prevent="if (window.adminDashboard) { window.adminDashboard.activeTab = 'operation-logs'; if (window.adminDashboard.operationLogs.length === 0) window.adminDashboard.fetchOperationLogs(); } else { window.location.href = '{{ route('admin.dashboard') }}'; }">
                                            <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 11 12 14 22 4"></polyline>
                                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                            </svg>
                                            <span>操作ログ</span>
                                        </a>
                                    </li>
                                </ul>
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
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
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
            <main class="main-content @if(request()->is('admin*')) admin-main-content @endif" role="main">
                {{-- 全ページ共通のフラッシュメッセージ表示 --}}
                @if(session('success'))
                    <div class="flash-message flash-success" role="alert" aria-live="polite" aria-atomic="true">
                        <svg class="flash-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="flash-message flash-error" role="alert" aria-live="assertive" aria-atomic="true">
                        <svg class="flash-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('status'))
                    <div class="flash-message flash-info" role="alert" aria-live="polite" aria-atomic="true">
                        <svg class="flash-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @yield('content')
            </main>
            <footer class="footer" role="contentinfo">
                <p>&copy; {{ date('Y') }} One Step</p>
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>

