// レイアウトコンポーネント
import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';
import './Layout.css';

const Layout = () => {
  const { user, logout, isUser, isGuide, isAdmin } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  // モバイルではデフォルトで非表示、デスクトップでは表示
  const [sidebarVisible, setSidebarVisible] = useState(() => window.innerWidth > 768);
  const [isMobile, setIsMobile] = useState(() => window.innerWidth <= 768);
  const [unreadMessageCount, setUnreadMessageCount] = useState(0);

  // ウィンドウリサイズ時にサイドバーの表示状態を更新
  useEffect(() => {
    const handleResize = () => {
      const mobile = window.innerWidth <= 768;
      setIsMobile(mobile);
      if (mobile) {
        setSidebarVisible(false);
      } else {
        setSidebarVisible(true);
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // 未読メッセージ数を取得
  useEffect(() => {
    if (!user) return;

    const fetchUnreadCount = async () => {
      try {
        const response = await axios.get('/chat/unread-count');
        setUnreadMessageCount(response.data.unread_count || 0);
      } catch (error) {
        console.error('未読メッセージ数取得エラー:', error);
      }
    };

    // 初回取得
    fetchUnreadCount();

    // 30秒ごとに未読メッセージ数を更新
    const interval = setInterval(fetchUnreadCount, 30000);

    // チャットページを開いたときに未読数を更新
    const handleChatOpened = () => {
      fetchUnreadCount();
    };
    window.addEventListener('chat-opened', handleChatOpened);

    return () => {
      clearInterval(interval);
      window.removeEventListener('chat-opened', handleChatOpened);
    };
  }, [user]);

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const toggleSidebar = () => {
    setSidebarVisible(!sidebarVisible);
  };

  return (
    <div className="layout">
      {sidebarVisible && isMobile && (
        <div 
          className="sidebar-overlay" 
          onClick={toggleSidebar}
          aria-hidden="true"
        />
      )}
      <aside className={`sidebar ${!sidebarVisible ? 'sidebar-hidden' : ''}`} role="navigation" aria-label="メインナビゲーション">
        <div className="sidebar-header">
          <Link to="/" className="sidebar-logo" aria-label="ホームへ戻る">
            <div className="logo-icon-wrapper">
              {/* <svg className="logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                  <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stopColor="#3b82f6" />
                    <stop offset="100%" stopColor="#8b5cf6" />
                  </linearGradient>
                </defs>
                <rect width="32" height="32" rx="8" fill="url(#logoGradient)"/>
                <path d="M16 8L20 12L16 16L12 12L16 8Z" fill="white" opacity="0.9"/>
                <path d="M8 16L12 20L16 16L12 12L8 16Z" fill="white" opacity="0.7"/>
                <path d="M24 16L20 12L16 16L20 20L24 16Z" fill="white" opacity="0.7"/>
                <path d="M16 24L12 20L16 16L20 20L16 24Z" fill="white" opacity="0.9"/>
                <circle cx="16" cy="16" r="2" fill="white"/>
              </svg> */}
              <img src="../src/logo.png" alt="ガイドマッチ" className="logo-icon" />

            </div>
            <span className="sidebar-logo-text">ガイドマッチ</span>
          </Link>
        </div>
        <nav className="nav">
            <ul className="nav-list">
              <li>
                <Link to="/" aria-current={location.pathname === '/' ? 'page' : undefined}>
                  <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                  <span>ダッシュボード</span>
                </Link>
              </li>
              {isUser && (
                <>
                  <li>
                    <Link to="/requests/new" aria-current={location.pathname === '/requests/new' ? 'page' : undefined}>
                      <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                      </svg>
                      <span>依頼作成</span>
                    </Link>
                  </li>
                  <li>
                    <Link to="/requests" aria-current={location.pathname === '/requests' ? 'page' : undefined}>
                      <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                      </svg>
                      <span>依頼一覧</span>
                    </Link>
                  </li>
                </>
              )}
              {isGuide && (
                <>
                  <li>
                    <Link to="/guide/requests" aria-current={location.pathname === '/guide/requests' ? 'page' : undefined}>
                      <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                      </svg>
                      <span>依頼一覧</span>
                    </Link>
                  </li>
                </>
              )}
              {isAdmin && (
                <li>
                  <Link to="/admin" aria-current={location.pathname === '/admin' ? 'page' : undefined}>
                    <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <circle cx="12" cy="12" r="3"></circle>
                      <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                    </svg>
                    <span>管理画面</span>
                  </Link>
                </li>
              )}
              
              <li>
                <Link to="/profile" aria-current={location.pathname === '/profile' ? 'page' : undefined}>
                  <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  <span>プロフィール</span>
                </Link>
              </li>
              <li className="nav-divider"></li>
              <li>
                <button onClick={handleLogout} className="btn-logout" aria-label="ログアウト">
                  <svg className="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                  </svg>
                  <span>ログアウト</span>
                </button>
              </li>
            </ul>
          </nav>
        </aside>
        <div className="main-wrapper">
          <header className="header" role="banner">
            <div className="header-content">
              <h1 className="logo">
                <button
                  className="menu-toggle-btn"
                  aria-label="メニューを開閉"
                  onClick={toggleSidebar}
                  aria-expanded={sidebarVisible}
                >
                  <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    <line x1="3" y1="7" x2="21" y2="7" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="17" x2="21" y2="17" />
                  </svg>
                </button>
                {/* <Link to="/" className="header-logo-link">
                  <div className="header-logo-icon-wrapper">
                    <svg className="header-logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="headerLogoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stopColor="#3b82f6" />
                          <stop offset="100%" stopColor="#8b5cf6" />
                        </linearGradient>
                      </defs>
                      <rect width="32" height="32" rx="8" fill="url(#headerLogoGradient)"/>
                      <path d="M16 8L20 12L16 16L12 12L16 8Z" fill="white" opacity="0.9"/>
                      <path d="M8 16L12 20L16 16L12 12L8 16Z" fill="white" opacity="0.7"/>
                      <path d="M24 16L20 12L16 16L20 20L24 16Z" fill="white" opacity="0.7"/>
                      <path d="M16 24L12 20L16 16L20 20L16 24Z" fill="white" opacity="0.9"/>
                      <circle cx="16" cy="16" r="2" fill="white"/>
                    </svg>
                  </div>
                  <span className="header-logo-text">ガイドマッチ</span>
                </Link> */}
              </h1>
          <div className="user-menu">
                <button 
                  className={`header-icon-btn ${unreadMessageCount > 0 ? 'has-notifications' : ''}`} 
                  aria-label="メッセージ" 
                  title={unreadMessageCount > 0 ? `未読メッセージ: ${unreadMessageCount}件` : 'メッセージ'}
                  onClick={() => setUnreadMessageCount(0)}
                >
                  <svg className="header-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                  </svg>
                  {unreadMessageCount > 0 && (
                    <span className="notification-badge" aria-label={`未読メッセージ ${unreadMessageCount}件`}>
                      {unreadMessageCount > 99 ? '99+' : unreadMessageCount}
                    </span>
                  )}
                </button>
                <div className="user-info">
                  <div className="user-avatar" aria-hidden="true">
                    {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                  </div>
            <span className="user-name" aria-label={`ログインユーザー: ${user?.name}`}>
              {user?.name}さん
            </span>
                </div>
          </div>
        </div>
      </header>
      <main className="main-content" role="main">
            <Outlet />
      </main>
      <footer className="footer" role="contentinfo">
            <p>&copy; {new Date().getFullYear()} ガイドマッチングアプリ</p>
      </footer>
        </div>
    </div>
  );
};

export default Layout;

