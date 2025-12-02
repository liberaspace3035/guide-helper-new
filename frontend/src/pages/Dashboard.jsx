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
  const [loading, setLoading] = useState(true);

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
        const [requestsResponse, matchingsResponse, usageStatsResponse] = await Promise.all([
          axios.get('/requests/my-requests'),
          axios.get('/matchings/my-matchings'),
          axios.get('/reports/usage-stats')
        ]);
        setMatchings(matchingsResponse.data.matchings);
        setStats({
          requests: requestsResponse.data.requests.length,
          matchings: matchingsResponse.data.matchings.length,
          pendingReports: 0
        });
        setUsageStats(usageStatsResponse.data);
      } else if (isGuide) {
        const [requestsResponse, matchingsResponse, reportsResponse, guideStatsResponse] = await Promise.all([
          axios.get('/requests/guide/available'),
          axios.get('/matchings/my-matchings'),
          axios.get('/reports/my-reports'),
          axios.get('/reports/guide-stats')
        ]);
        setMatchings(matchingsResponse.data.matchings);
        setStats({
          availableRequests: requestsResponse.data.requests.length,
          matchings: matchingsResponse.data.matchings.length,
          reports: reportsResponse.data.reports.length
        });
        setUsageStats(guideStatsResponse.data);
      }
    } catch (error) {
      console.error('ダッシュボードデータ取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  return (
    <div className="dashboard">
      <h1>ダッシュボード</h1>
      <p className="welcome-message">ようこそ、{user?.name}さん</p>

      {/* 通知セクション */}
      {notifications.length > 0 && (
        <section className="notifications-section" aria-label="通知">
          <h2>通知</h2>
          <ul className="notification-list">
            {notifications.map(notif => (
              <li key={notif.id} className="notification-item">
                <strong>{notif.title}</strong>
                <p>{notif.message}</p>
                <small>{new Date(notif.created_at).toLocaleString('ja-JP')}</small>
              </li>
            ))}
          </ul>
        </section>
      )}

      {/* ユーザー向けダッシュボード */}
      {isUser && (
        <>
        <div className="dashboard-cards">
          <div className="card">
            <h3>依頼作成</h3>
            <p>新しい依頼を作成します</p>
            <Link to="/requests/new" className="btn-primary btn-icon">
              <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>依頼を作成</span>
            </Link>
          </div>
          <div className="card">
            <h3>依頼一覧</h3>
            <p>作成した依頼を確認します</p>
            <Link to="/requests" className="btn-secondary btn-icon">
              <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
              <span>依頼一覧を見る</span>
            </Link>
          </div>
            {stats && (
              <div className="card">
                <h3>統計情報</h3>
                <ul className="stats-list">
                  <li>依頼数: {stats.requests}</li>
                  <li>マッチング数: {stats.matchings}</li>
                </ul>
              </div>
            )}
            {usageStats && (
              <div className="card">
                <h3>利用時間</h3>
                <div className="usage-stats">
                  <div className="usage-current-month">
                    <h4>今月の利用時間</h4>
                    <p className="usage-total">{usageStats.current_month.total_hours}時間</p>
                    <div className="usage-by-type">
                      <div className="usage-type-item">
                        <span className="usage-type-label">外出:</span>
                        <span className="usage-type-value">{usageStats.current_month.by_type['外出'] || 0}時間</span>
                      </div>
                      <div className="usage-type-item">
                        <span className="usage-type-label">自宅:</span>
                        <span className="usage-type-value">{usageStats.current_month.by_type['自宅'] || 0}時間</span>
                      </div>
                    </div>
                  </div>
                  {usageStats.monthly && usageStats.monthly.length > 0 && (
                    <div className="usage-monthly">
                      <h4>月別利用時間（過去12ヶ月）</h4>
                      <div className="usage-monthly-list">
                        {usageStats.monthly.slice(0, 6).map((stat, index) => (
                          <div key={index} className="usage-monthly-item">
                            <span className="usage-month">{stat.month}</span>
                            <span className="usage-hours">{stat.total_hours}時間</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
          
          {/* マッチング一覧（チャット可能なもの） */}
          {matchings.length > 0 && (
            <section className="matchings-section" aria-label="マッチング一覧">
              <h2>マッチング一覧</h2>
              <div className="matchings-list">
                {matchings.map(matching => (
                  <div key={matching.id} className="matching-card">
                    <div className="matching-info">
                      <h3>{matching.request_type} - {matching.masked_address}</h3>
                      <p>ガイド: {matching.guide_name}</p>
                      <p>日時: {matching.request_date} {matching.request_time}</p>
                    </div>
                    <div className="matching-actions">
                      <Link
                        to={`/chat/${matching.id}`}
                        className="btn-primary btn-icon"
                        aria-label="チャットを開く"
                      >
                        <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>チャットを開く</span>
                      </Link>
                      <Link
                        to={`/matchings/${matching.id}`}
                        className="btn-secondary btn-icon"
                        aria-label="詳細を確認"
                      >
                        <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                          <polyline points="14 2 14 8 20 8"></polyline>
                          <line x1="16" y1="13" x2="8" y2="13"></line>
                          <line x1="16" y1="17" x2="8" y2="17"></line>
                          <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>詳細</span>
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}
        </>
      )}

      {/* ガイド向けダッシュボード */}
      {isGuide && (
        <>
          <div className="dashboard-cards">
            <div className="card">
              <h3>依頼一覧</h3>
              <p>承諾可能な依頼を確認します</p>
              <Link to="/guide/requests" className="btn-primary btn-icon">
                <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <line x1="8" y1="6" x2="21" y2="6"></line>
                  <line x1="8" y1="12" x2="21" y2="12"></line>
                  <line x1="8" y1="18" x2="21" y2="18"></line>
                  <line x1="3" y1="6" x2="3.01" y2="6"></line>
                  <line x1="3" y1="12" x2="3.01" y2="12"></line>
                  <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>依頼一覧を見る</span>
              </Link>
            </div>
            {stats && (
              <div className="card">
                <h3>統計情報</h3>
                <ul className="stats-list">
                  <li>利用可能な依頼: {stats.availableRequests}</li>
                  <li>マッチング数: {stats.matchings}</li>
                  <li>報告書数: {stats.reports}</li>
                </ul>
              </div>
            )}
            {usageStats && (
              <div className="card">
                <h3>ガイド時間</h3>
                <div className="usage-stats">
                  <div className="usage-current-month">
                    <h4>今月のガイド時間</h4>
                    <p className="usage-total">{usageStats.current_month.total_hours}時間</p>
                    <div className="usage-by-type">
                      <div className="usage-type-item">
                        <span className="usage-type-label">外出:</span>
                        <span className="usage-type-value">{usageStats.current_month.by_type['外出'] || 0}時間</span>
                      </div>
                      <div className="usage-type-item">
                        <span className="usage-type-label">自宅:</span>
                        <span className="usage-type-value">{usageStats.current_month.by_type['自宅'] || 0}時間</span>
                      </div>
                    </div>
                  </div>
                  {usageStats.monthly && usageStats.monthly.length > 0 && (
                    <div className="usage-monthly">
                      <h4>月別ガイド時間（過去12ヶ月）</h4>
                      <div className="usage-monthly-list">
                        {usageStats.monthly.slice(0, 6).map((stat, index) => (
                          <div key={index} className="usage-monthly-item">
                            <span className="usage-month">{stat.month}</span>
                            <span className="usage-hours">{stat.total_hours}時間</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
          
          {/* マッチング一覧（チャット可能なもの） */}
          {matchings.length > 0 && (
            <section className="matchings-section" aria-label="マッチング一覧">
              <h2>マッチング一覧</h2>
              <div className="matchings-list">
                {matchings.map(matching => (
                  <div key={matching.id} className="matching-card">
                    <div className="matching-info">
                      <h3>{matching.request_type} - {matching.masked_address}</h3>
                      <p>ユーザー: {matching.user_name}</p>
                      <p>日時: {matching.request_date} {matching.request_time}</p>
                    </div>
                    <div className="matching-actions">
                      <Link
                        to={`/chat/${matching.id}`}
                        className="btn-primary btn-icon"
                        aria-label="チャットを開く"
                      >
                        <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>チャットを開く</span>
                      </Link>
                      <Link
                        to={`/matchings/${matching.id}`}
                        className="btn-secondary btn-icon"
                        aria-label="詳細を確認"
                      >
                        <svg className="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                          <polyline points="14 2 14 8 20 8"></polyline>
                          <line x1="16" y1="13" x2="8" y2="13"></line>
                          <line x1="16" y1="17" x2="8" y2="17"></line>
                          <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>詳細</span>
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}
        </>
      )}

      {/* 管理者向けダッシュボード */}
      {isAdmin && (
        <div className="dashboard-cards">
          <div className="card">
            <h3>管理画面</h3>
            <p>システム全体を管理します</p>
            <Link to="/admin" className="btn-primary">
              管理画面へ
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;

