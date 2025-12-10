// ダッシュボードページ
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';
import './Dashboard.css';

const Dashboard = () => {
  const { user, isUser, isGuide, isAdmin } = useAuth();
  const [stats, setStats] = useState(null);
  const [usageStats, setUsageStats] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [matchings, setMatchings] = useState([]);
  const [pendingReports, setPendingReports] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // 月別表示用の状態
  const [selectedMonth, setSelectedMonth] = useState(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  });
  const [selectedMonthStats, setSelectedMonthStats] = useState(null);
  const [loadingMonthStats, setLoadingMonthStats] = useState(false);

  // 時間帯に応じた挨拶
  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'おはようございます';
    if (hour < 18) return 'こんにちは';
    return 'こんばんは';
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      // 通知取得
      const notifResponse = await axios.get('/notifications?unread_only=true');
      setNotifications(notifResponse.data.notifications.slice(0, 5));

      // ロール別の統計情報取得
      if (isUser) {
        const [requestsResponse, matchingsResponse, usageStatsResponse, pendingReportsResponse] = await Promise.all([
          axios.get('/requests/my-requests'),
          axios.get('/matchings/my-matchings'),
          axios.get('/reports/usage-stats'),
          axios.get('/reports/user/pending').catch(() => ({ data: { reports: [] } }))
        ]);
        setMatchings(matchingsResponse.data.matchings.filter(m => m.status === 'matched' || m.status === 'in_progress'));
        setPendingReports(pendingReportsResponse.data.reports || []);
        
        const activeMatchings = matchingsResponse.data.matchings.filter(m => m.status === 'matched' || m.status === 'in_progress');
        const completedMatchings = matchingsResponse.data.matchings.filter(m => m.status === 'completed');
        
        setStats({
          requests: requestsResponse.data.requests.length,
          activeMatchings: activeMatchings.length,
          completedMatchings: completedMatchings.length,
          pendingReports: pendingReportsResponse.data.reports?.length || 0
        });
        setUsageStats(usageStatsResponse.data);
      } else if (isGuide) {
        const [requestsResponse, matchingsResponse, reportsResponse, guideStatsResponse] = await Promise.all([
          axios.get('/requests/guide/available'),
          axios.get('/matchings/my-matchings'),
          axios.get('/reports/my-reports'),
          axios.get('/reports/guide-stats')
        ]);
        
        const activeMatchings = matchingsResponse.data.matchings.filter(m => m.status === 'matched' || m.status === 'in_progress');
        const completedMatchings = matchingsResponse.data.matchings.filter(m => m.status === 'completed');
        const pendingReports = reportsResponse.data.reports.filter(r => r.status === 'draft' || r.status === 'revision_requested');
        
        setMatchings(activeMatchings);
        setStats({
          availableRequests: requestsResponse.data.requests.length,
          activeMatchings: activeMatchings.length,
          completedMatchings: completedMatchings.length,
          pendingReports: pendingReports.length,
          totalReports: reportsResponse.data.reports.length
        });
        setUsageStats(guideStatsResponse.data);
      }
    } catch (error) {
      console.error('ダッシュボードデータ取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  // 選択した月の利用時間を取得
  const fetchMonthStats = async (monthString) => {
    if (!isUser && !isGuide) return;
    
    setLoadingMonthStats(true);
    try {
      const [year, month] = monthString.split('-');
      const endpoint = isUser ? '/reports/usage-stats' : '/reports/guide-stats';
      const response = await axios.get(`${endpoint}?year=${year}&month=${month}`);
      setSelectedMonthStats(response.data.current_month);
    } catch (error) {
      console.error('月別統計取得エラー:', error);
      setSelectedMonthStats(null);
    } finally {
      setLoadingMonthStats(false);
    }
  };

  // 月を変更したときの処理
  const handleMonthChange = (e) => {
    const newMonth = e.target.value;
    setSelectedMonth(newMonth);
    fetchMonthStats(newMonth);
  };

  // 過去12ヶ月のオプションを生成
  const getMonthOptions = () => {
    const options = [];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
      const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      const label = `${date.getFullYear()}年${date.getMonth() + 1}月`;
      options.push({ value, label });
    }
    return options;
  };

  // 日付フォーマット
  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric', weekday: 'short' });
  };

  // ステータスバッジ
  const getStatusBadge = (status) => {
    const statusMap = {
      'matched': { label: 'マッチング中', class: 'badge-info' },
      'in_progress': { label: '進行中', class: 'badge-warning' },
      'completed': { label: '完了', class: 'badge-success' },
      'cancelled': { label: 'キャンセル', class: 'badge-error' }
    };
    const statusInfo = statusMap[status] || { label: status, class: 'badge-default' };
    return <span className={`status-badge ${statusInfo.class}`}>{statusInfo.label}</span>;
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>読み込み中...</p>
      </div>
    );
  }

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <div className="dashboard-title">
          <h1>{getGreeting()}、{user?.name}さん</h1>
          <p className="welcome-message">
            {isUser && '今日も素敵な一日をお過ごしください'}
            {isGuide && '本日もガイド活動をよろしくお願いします'}
            {isAdmin && 'システム管理画面へようこそ'}
          </p>
        </div>
      </div>

      {/* 通知セクション */}
      {notifications.length > 0 && (
        <section className="notifications-section" aria-label="通知">
          <div className="section-header">
            <h2>
              <svg className="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              通知
            </h2>
            <span className="notification-count">{notifications.length}件</span>
          </div>
          <ul className="notification-list">
            {notifications.map(notif => (
              <li key={notif.id} className="notification-item">
                <div className="notification-content">
                  <strong>{notif.title}</strong>
                  <p>{notif.message}</p>
                </div>
                <small>{new Date(notif.created_at).toLocaleString('ja-JP')}</small>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* ユーザー向けダッシュボード */}
      {isUser && (
        <>
          {/* クイックアクション */}
          <section className="quick-actions">
            <Link to="/requests/new" className="quick-action-btn primary">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>新規依頼を作成</span>
            </Link>
            <Link to="/requests" className="quick-action-btn secondary">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
              <span>依頼一覧</span>
            </Link>
          </section>

          {/* 承認待ち報告書 */}
          {pendingReports.length > 0 && (
            <section className="pending-reports-section">
              <div className="section-header">
                <h2>
                  <svg className="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                  </svg>
                  承認待ちの報告書
                </h2>
                <span className="pending-count">{pendingReports.length}件</span>
              </div>
              <div className="pending-reports-list">
                {pendingReports.slice(0, 3).map(report => (
                  <Link key={report.id} to={`/reports/${report.id}`} className="pending-report-item">
                    <div className="report-info">
                      <span className="report-type">{report.request_type}</span>
                      <span className="report-date">{formatDate(report.actual_date)}</span>
                    </div>
                    <span className="report-guide">ガイド: {report.guide_name}</span>
                  </Link>
                ))}
              </div>
            </section>
          )}

          {/* 統計カード */}
          <div className="dashboard-cards">
            {stats && (
              <div className="card stats-card">
                <div className="card-header">
                  <svg className="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 21H3"></path>
                    <path d="M21 21V10"></path>
                    <path d="M3 21V10"></path>
                    <path d="M7 21V14"></path>
                    <path d="M11 21V6"></path>
                    <path d="M15 21V10"></path>
                    <path d="M19 21V4"></path>
                  </svg>
                  <h3>統計情報</h3>
                </div>
                <div className="stats-grid">
                  <div className="stat-item">
                    <span className="stat-value">{stats.requests}</span>
                    <span className="stat-label">総依頼数</span>
                  </div>
                  <div className="stat-item highlight">
                    <span className="stat-value">{stats.activeMatchings}</span>
                    <span className="stat-label">進行中</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-value">{stats.completedMatchings}</span>
                    <span className="stat-label">完了</span>
                  </div>
                  {stats.pendingReports > 0 && (
                    <div className="stat-item alert">
                      <span className="stat-value">{stats.pendingReports}</span>
                      <span className="stat-label">承認待ち</span>
                    </div>
                  )}
                </div>
              </div>
            )}

            {usageStats && (
              <div className="card usage-card">
                <div className="card-header usage-card-header">
                  <div className="card-header-left">
                    <svg className="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <h3>利用時間</h3>
                  </div>
                  <div className="month-selector-header">
                    <select 
                      value={selectedMonth} 
                      onChange={handleMonthChange}
                      className="month-select"
                      aria-label="月を選択"
                    >
                      {getMonthOptions().map(option => (
                        <option key={option.value} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
                <div className="usage-stats">
                  {loadingMonthStats ? (
                    <div className="loading-text">
                      <div className="loading-spinner small"></div>
                    </div>
                  ) : (
                    <div className="usage-content">
                      <p className="usage-total">
                        {(selectedMonthStats || usageStats.current_month).total_hours}
                        <span className="usage-unit">時間</span>
                      </p>
                      <div className="usage-breakdown">
                        <div className="usage-bar-item">
                          <div className="usage-bar-header">
                            <span className="usage-bar-label">
                              <span className="usage-dot outing"></span>
                              外出
                            </span>
                            <span className="usage-bar-value">
                              {(selectedMonthStats || usageStats.current_month).by_type['外出'] || 0}時間
                            </span>
                          </div>
                          <div className="usage-bar">
                            <div 
                              className="usage-bar-fill outing" 
                              style={{ 
                                width: `${Math.min(((selectedMonthStats || usageStats.current_month).by_type['外出'] || 0) / Math.max((selectedMonthStats || usageStats.current_month).total_hours, 1) * 100, 100)}%` 
                              }}
                            ></div>
                          </div>
                        </div>
                        <div className="usage-bar-item">
                          <div className="usage-bar-header">
                            <span className="usage-bar-label">
                              <span className="usage-dot home"></span>
                              自宅
                            </span>
                            <span className="usage-bar-value">
                              {(selectedMonthStats || usageStats.current_month).by_type['自宅'] || 0}時間
                            </span>
                          </div>
                          <div className="usage-bar">
                            <div 
                              className="usage-bar-fill home" 
                              style={{ 
                                width: `${Math.min(((selectedMonthStats || usageStats.current_month).by_type['自宅'] || 0) / Math.max((selectedMonthStats || usageStats.current_month).total_hours, 1) * 100, 100)}%` 
                              }}
                            ></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
          
          {/* マッチング一覧 */}
          {matchings.length > 0 && (
            <section className="matchings-section" aria-label="マッチング一覧">
              <div className="section-header">
                <h2>
                  <svg className="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                  進行中のマッチング
                </h2>
              </div>
              <div className="matchings-list">
                {matchings.map(matching => (
                  <div key={matching.id} className="matching-card">
                    <div className="matching-header">
                      {getStatusBadge(matching.status)}
                      <span className="matching-type">{matching.request_type}</span>
                    </div>
                    <div className="matching-info">
                      <h3>{matching.masked_address}</h3>
                      <div className="matching-details">
                        <p>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                          </svg>
                          ガイド: {matching.guide_name}
                        </p>
                        <p>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                          </svg>
                          {formatDate(matching.request_date)} {matching.request_time}
                        </p>
                      </div>
                    </div>
                    <div className="matching-actions">
                      <Link to={`/chat/${matching.id}`} className="btn-primary btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>チャット</span>
                      </Link>
                      <Link to={`/matchings/${matching.id}`} className="btn-secondary btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="12" y1="16" x2="12" y2="12"></line>
                          <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>詳細</span>
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}

          {/* マッチングがない場合 */}
          {matchings.length === 0 && (
            <section className="empty-state">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              <h3>現在進行中のマッチングはありません</h3>
              <p>新しい依頼を作成して、ガイドとマッチングしましょう</p>
              <Link to="/requests/new" className="btn-primary">
                依頼を作成する
              </Link>
            </section>
          )}
        </>
      )}

      {/* ガイド向けダッシュボード */}
      {isGuide && (
        <>
          {/* クイックアクション */}
          <section className="quick-actions">
            <Link to="/guide/requests" className="quick-action-btn primary">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
              <span>依頼を探す</span>
              {stats?.availableRequests > 0 && (
                <span className="action-badge">{stats.availableRequests}</span>
              )}
            </Link>
          </section>

          {/* 統計カード */}
          <div className="dashboard-cards">
            {stats && (
              <div className="card stats-card">
                <div className="card-header">
                  <svg className="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 21H3"></path>
                    <path d="M21 21V10"></path>
                    <path d="M3 21V10"></path>
                    <path d="M7 21V14"></path>
                    <path d="M11 21V6"></path>
                    <path d="M15 21V10"></path>
                    <path d="M19 21V4"></path>
                  </svg>
                  <h3>統計情報</h3>
                </div>
                <div className="stats-grid">
                  <div className="stat-item highlight">
                    <span className="stat-value">{stats.availableRequests}</span>
                    <span className="stat-label">新規依頼</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-value">{stats.activeMatchings}</span>
                    <span className="stat-label">進行中</span>
                  </div>
                  <div className="stat-item">
                    <span className="stat-value">{stats.completedMatchings}</span>
                    <span className="stat-label">完了</span>
                  </div>
                  {stats.pendingReports > 0 && (
                    <div className="stat-item alert">
                      <span className="stat-value">{stats.pendingReports}</span>
                      <span className="stat-label">要報告書</span>
                    </div>
                  )}
                </div>
              </div>
            )}

            {usageStats && (
              <div className="card usage-card">
                <div className="card-header usage-card-header">
                  <div className="card-header-left">
                    <svg className="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <h3>ガイド時間</h3>
                  </div>
                  <div className="month-selector-header">
                    <select 
                      value={selectedMonth} 
                      onChange={handleMonthChange}
                      className="month-select"
                      aria-label="月を選択"
                    >
                      {getMonthOptions().map(option => (
                        <option key={option.value} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
                <div className="usage-stats">
                  {loadingMonthStats ? (
                    <div className="loading-text">
                      <div className="loading-spinner small"></div>
                    </div>
                  ) : (
                    <div className="usage-content">
                      <p className="usage-total">
                        {(selectedMonthStats || usageStats.current_month).total_hours}
                        <span className="usage-unit">時間</span>
                      </p>
                      <div className="usage-breakdown">
                        <div className="usage-bar-item">
                          <div className="usage-bar-header">
                            <span className="usage-bar-label">
                              <span className="usage-dot outing"></span>
                              外出
                            </span>
                            <span className="usage-bar-value">
                              {(selectedMonthStats || usageStats.current_month).by_type['外出'] || 0}時間
                            </span>
                          </div>
                          <div className="usage-bar">
                            <div 
                              className="usage-bar-fill outing" 
                              style={{ 
                                width: `${Math.min(((selectedMonthStats || usageStats.current_month).by_type['外出'] || 0) / Math.max((selectedMonthStats || usageStats.current_month).total_hours, 1) * 100, 100)}%` 
                              }}
                            ></div>
                          </div>
                        </div>
                        <div className="usage-bar-item">
                          <div className="usage-bar-header">
                            <span className="usage-bar-label">
                              <span className="usage-dot home"></span>
                              自宅
                            </span>
                            <span className="usage-bar-value">
                              {(selectedMonthStats || usageStats.current_month).by_type['自宅'] || 0}時間
                            </span>
                          </div>
                          <div className="usage-bar">
                            <div 
                              className="usage-bar-fill home" 
                              style={{ 
                                width: `${Math.min(((selectedMonthStats || usageStats.current_month).by_type['自宅'] || 0) / Math.max((selectedMonthStats || usageStats.current_month).total_hours, 1) * 100, 100)}%` 
                              }}
                            ></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
          
          {/* マッチング一覧 */}
          {matchings.length > 0 && (
            <section className="matchings-section" aria-label="マッチング一覧">
              <div className="section-header">
                <h2>
                  <svg className="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                  進行中のマッチング
                </h2>
              </div>
              <div className="matchings-list">
                {matchings.map(matching => (
                  <div key={matching.id} className="matching-card">
                    <div className="matching-header">
                      {getStatusBadge(matching.status)}
                      <span className="matching-type">{matching.request_type}</span>
                    </div>
                    <div className="matching-info">
                      <h3>{matching.masked_address}</h3>
                      <div className="matching-details">
                        <p>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                          </svg>
                          ユーザー: {matching.user_name}
                        </p>
                        <p>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                          </svg>
                          {formatDate(matching.request_date)} {matching.request_time}
                        </p>
                      </div>
                    </div>
                    <div className="matching-actions">
                      <Link to={`/chat/${matching.id}`} className="btn-primary btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>チャット</span>
                      </Link>
                      <Link to={`/matchings/${matching.id}`} className="btn-secondary btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                          <circle cx="12" cy="12" r="10"></circle>
                          <line x1="12" y1="16" x2="12" y2="12"></line>
                          <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>詳細</span>
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}

          {/* マッチングがない場合 */}
          {matchings.length === 0 && (
            <section className="empty-state">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
              <h3>現在進行中のマッチングはありません</h3>
              <p>新しい依頼を確認して、ガイドを始めましょう</p>
              <Link to="/guide/requests" className="btn-primary">
                依頼を探す
              </Link>
            </section>
          )}
        </>
      )}

      {/* 管理者向けダッシュボード */}
      {isAdmin && (
        <div className="dashboard-cards">
          <div className="card admin-card">
            <div className="card-header">
              <svg className="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
              </svg>
              <h3>管理画面</h3>
            </div>
            <p>ユーザー、ガイド、マッチングの管理を行います</p>
            <Link to="/admin" className="btn-primary btn-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
              <span>管理画面へ</span>
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
