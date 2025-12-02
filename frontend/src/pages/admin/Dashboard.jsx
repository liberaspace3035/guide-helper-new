// 管理者ダッシュボード
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import './Dashboard.css';

const AdminDashboard = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [requests, setRequests] = useState([]);
  const [acceptances, setAcceptances] = useState([]);
  const [reports, setReports] = useState([]);
  const [users, setUsers] = useState([]);
  const [guides, setGuides] = useState([]);
  const [stats, setStats] = useState(null);
  const [autoMatching, setAutoMatching] = useState(false);
  const [loading, setLoading] = useState(true);

  const fetchDashboardData = async () => {
    try {
      const [requestsRes, acceptancesRes, reportsRes, settingsRes, statsRes, userStatsRes] = await Promise.all([
        axios.get('/admin/requests'),
        axios.get('/admin/acceptances'),
        axios.get('/admin/reports'),
        axios.get('/admin/settings/auto-matching'),
        axios.get('/admin/stats'),
        axios.get('/users/stats')
      ]);

      setRequests(requestsRes.data.requests);
      setAcceptances(acceptancesRes.data.acceptances);
      setReports(reportsRes.data.reports);
      setAutoMatching(settingsRes.data.auto_matching);
      
      // 統計情報をマージ（/admin/statsと/users/statsの両方から取得）
      const mergedStats = {
        ...statsRes.data,
        users: userStatsRes.data.users,
        guides: userStatsRes.data.guides
      };
      setStats(mergedStats);
    } catch (error) {
      console.error('ダッシュボードデータ取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchUsers = async () => {
    try {
      const response = await axios.get('/admin/users');
      setUsers(response.data.users);
      console.log("==========>", response.data.users);
    } catch (error) {
      console.error('ユーザー一覧取得エラー:', error);
      alert('ユーザー一覧の取得に失敗しました');
    }
  };

  const fetchGuides = async () => {
    try {
      const response = await axios.get('/admin/guides');
      setGuides(response.data.guides);
    } catch (error) {
      console.error('ガイド一覧取得エラー:', error);
      alert('ガイド一覧の取得に失敗しました');
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  useEffect(() => {
    if (activeTab === 'users') {
      fetchUsers();
    } else if (activeTab === 'guides') {
      fetchGuides();
    }
  }, [activeTab]);

  const handleAutoMatchingToggle = async () => {
    try {
      await axios.put('/admin/settings/auto-matching', {
        auto_matching: !autoMatching
      });
      setAutoMatching(!autoMatching);
      alert('自動マッチング設定を更新しました');
    } catch (error) {
      alert('設定の更新に失敗しました');
      console.error(error);
    }
  };

  const handleMatchingApprove = async (requestId, guideId) => {
    try {
      await axios.post('/admin/matchings/approve', {
        request_id: requestId,
        guide_id: guideId
      });
      alert('マッチングを承認しました');
      fetchDashboardData();
    } catch (error) {
      alert('マッチング承認に失敗しました');
      console.error(error);
    }
  };

  const handleMatchingReject = async (requestId, guideId) => {
    try {
      await axios.post('/admin/matchings/reject', {
        request_id: requestId,
        guide_id: guideId
      });
      alert('マッチングを却下しました');
      fetchDashboardData();
    } catch (error) {
      alert('マッチング却下に失敗しました');
      console.error(error);
    }
  };

  const handleCSVExport = async (type) => {
    try {
      const url = type === 'reports' 
        ? '/admin/reports/csv'
        : `/admin/usage/csv`;
      window.open(url, '_blank');
    } catch (error) {
      alert('CSV出力に失敗しました');
      console.error(error);
    }
  };

  const handleUserApprove = async (userId) => {
    if (!confirm('このユーザーを承認しますか？')) return;
    try {
      await axios.put(`/admin/users/${userId}/approve`);
      alert('ユーザーを承認しました');
      fetchUsers();
    } catch (error) {
      alert('ユーザー承認に失敗しました');
      console.error(error);
    }
  };

  const handleUserReject = async (userId) => {
    if (!confirm('このユーザーを拒否しますか？')) return;
    try {
      await axios.put(`/admin/users/${userId}/reject`);
      alert('ユーザーを拒否しました');
      fetchUsers();
    } catch (error) {
      alert('ユーザー拒否に失敗しました');
      console.error(error);
    }
  };

  const handleGuideApprove = async (guideId) => {
    if (!confirm('このガイドを承認しますか？')) return;
    try {
      await axios.put(`/admin/guides/${guideId}/approve`);
      alert('ガイドを承認しました');
      fetchGuides();
    } catch (error) {
      alert('ガイド承認に失敗しました');
      console.error(error);
    }
  };

  const handleGuideReject = async (guideId) => {
    if (!confirm('このガイドを拒否しますか？')) return;
    try {
      await axios.put(`/admin/guides/${guideId}/reject`);
      alert('ガイドを拒否しました');
      fetchGuides();
    } catch (error) {
      alert('ガイド拒否に失敗しました');
      console.error(error);
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  return (
    <div className="admin-dashboard">
      <h1>管理画面</h1>

      {/* タブナビゲーション */}
      <div className="admin-tabs">
        <button
          className={`admin-tab ${activeTab === 'dashboard' ? 'active' : ''}`}
          onClick={() => setActiveTab('dashboard')}
        >
          ダッシュボード
        </button>
        <button
          className={`admin-tab ${activeTab === 'users' ? 'active' : ''}`}
          onClick={() => setActiveTab('users')}
        >
          ユーザー管理
        </button>
        <button
          className={`admin-tab ${activeTab === 'guides' ? 'active' : ''}`}
          onClick={() => setActiveTab('guides')}
        >
          ガイド管理
        </button>
      </div>

      {/* ダッシュボードタブ */}
      {activeTab === 'dashboard' && (
        <>
      {/* 統計情報セクション */}
      {stats && (
        <section className="admin-section">
          <h2>統計情報</h2>
          <div className="stats-grid">
            <div className="stat-card">
              <h3>ユーザー</h3>
              <div className="stat-content">
                <div className="stat-item">
                  <span className="stat-label">総数:</span>
                  <span className="stat-value">{stats.users.total}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">承認済み:</span>
                  <span className="stat-value approved">{stats.users.approved}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">未承認:</span>
                  <span className="stat-value pending">{stats.users.pending}</span>
                </div>
              </div>
            </div>
            <div className="stat-card">
              <h3>ガイド</h3>
              <div className="stat-content">
                <div className="stat-item">
                  <span className="stat-label">総数:</span>
                  <span className="stat-value">{stats.guides.total}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">承認済み:</span>
                  <span className="stat-value approved">{stats.guides.approved}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">未承認:</span>
                  <span className="stat-value pending">{stats.guides.pending}</span>
                </div>
              </div>
            </div>
            <div className="stat-card">
              <h3>マッチング</h3>
              <div className="stat-content">
                <div className="stat-item">
                  <span className="stat-label">総数:</span>
                  <span className="stat-value">{stats.matchings.total || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">マッチング済み:</span>
                  <span className="stat-value">{stats.matchings.matched || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">進行中:</span>
                  <span className="stat-value">{stats.matchings.in_progress || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">完了:</span>
                  <span className="stat-value approved">{stats.matchings.completed || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">キャンセル:</span>
                  <span className="stat-value">{stats.matchings.cancelled || 0}</span>
                </div>
              </div>
            </div>
            <div className="stat-card">
              <h3>依頼</h3>
              <div className="stat-content">
                <div className="stat-item">
                  <span className="stat-label">総数:</span>
                  <span className="stat-value">{stats.requests.total || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">待機中:</span>
                  <span className="stat-value pending">{stats.requests.pending || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">ガイド承諾済み:</span>
                  <span className="stat-value">{stats.requests.guide_accepted || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">マッチング済み:</span>
                  <span className="stat-value">{stats.requests.matched || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">進行中:</span>
                  <span className="stat-value">{stats.requests.in_progress || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">完了:</span>
                  <span className="stat-value approved">{stats.requests.completed || 0}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">キャンセル:</span>
                  <span className="stat-value">{stats.requests.cancelled || 0}</span>
                </div>
              </div>
            </div>
          </div>
        </section>
      )}

      <section className="admin-section">
        <h2>設定</h2>
        <div className="setting-item">
          <label>
            <input
              type="checkbox"
              checked={autoMatching}
              onChange={handleAutoMatchingToggle}
            />
            自動マッチング
          </label>
          <p className="setting-description">
            {autoMatching 
              ? 'ガイドが承諾すると自動的にマッチングが成立します'
              : 'ガイドが承諾しても管理者の承認が必要です'}
          </p>
        </div>
      </section>

      <section className="admin-section">
        <h2>承諾待ち一覧</h2>
        {acceptances.length === 0 ? (
          <p>承諾待ちの依頼はありません</p>
        ) : (
          <div className="table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>依頼ID</th>
                  <th>ユーザー</th>
                  <th>ガイド</th>
                  <th>日時</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                {acceptances.map(acc => (
                  <tr key={acc.id}>
                    <td>{acc.request_id}</td>
                    <td>{acc.user_name}</td>
                    <td>{acc.guide_name}</td>
                    <td>{acc.request_date} {acc.request_time}</td>
                    <td>
                      <button
                        onClick={() => handleMatchingApprove(acc.request_id, acc.guide_id)}
                        className="btn-primary btn-sm"
                      >
                        承認
                      </button>
                      <button
                        onClick={() => handleMatchingReject(acc.request_id, acc.guide_id)}
                        className="btn-secondary btn-sm"
                      >
                        却下
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <section className="admin-section">
        <h2>報告書一覧</h2>
        <div className="section-actions">
          <button
            onClick={() => handleCSVExport('reports')}
            className="btn-secondary"
          >
            報告書CSV出力
          </button>
          <button
            onClick={() => handleCSVExport('usage')}
            className="btn-secondary"
          >
            利用実績CSV出力
          </button>
        </div>
        {reports.length === 0 ? (
          <p>報告書はありません</p>
        ) : (
          <div className="table-container">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>ユーザー</th>
                  <th>ガイド</th>
                  <th>実施日</th>
                  <th>ステータス</th>
                </tr>
              </thead>
              <tbody>
                {reports.map(report => (
                  <tr key={report.id}>
                    <td>{report.id}</td>
                    <td>{report.user_name}</td>
                    <td>{report.guide_name}</td>
                    <td>{report.actual_date || '-'}</td>
                    <td>
                      <span className={`status-badge ${
                        report.status === 'approved' ? 'status-approved' :
                        report.status === 'submitted' ? 'status-pending' :
                        'status-draft'
                      }`}>
                        {report.status === 'approved' ? '承認済み' :
                         report.status === 'submitted' ? '承認待ち' :
                         '下書き'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
        </>
      )}

      {/* ユーザー管理タブ */}
      {activeTab === 'users' && (
        <section className="admin-section">
          <h2>ユーザー管理</h2>
          {users.length === 0 ? (
            <p>ユーザーは登録されていません</p>
          ) : (
            <div className="table-container">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>電話番号</th>
                    <th>登録日</th>
                    <th>承認状態</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map(user => (
                    <tr key={user.id}>
                      <td>{user.id}</td>
                      <td>{user.name}</td>
                      <td>{user.email}</td>
                      <td>{user.phone || '-'}</td>
                      <td>{new Date(user.created_at).toLocaleDateString('ja-JP')}</td>
                      <td>
                        <span className={`status-badge ${user.is_allowed ? 'status-approved' : 'status-pending'}`}>
                          {user.is_allowed ? '承認済み' : '未承認'}
                        </span>
                      </td>
                      <td>
                        {!user.is_allowed ? (
                          <button
                            onClick={() => handleUserApprove(user.id)}
                            className="btn-primary btn-sm"
                          >
                            承認
                          </button>
                        ) : (
                          <button
                            onClick={() => handleUserReject(user.id)}
                            className="btn-secondary btn-sm"
                          >
                            拒否
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      )}

      {/* ガイド管理タブ */}
      {activeTab === 'guides' && (
        <section className="admin-section">
          <h2>ガイド管理</h2>
          {guides.length === 0 ? (
            <p>ガイドは登録されていません</p>
          ) : (
            <div className="table-container">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>電話番号</th>
                    <th>登録日</th>
                    <th>承認状態</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  {guides.map(guide => (
                    <tr key={guide.id}>
                      <td>{guide.id}</td>
                      <td>{guide.name}</td>
                      <td>{guide.email}</td>
                      <td>{guide.phone || '-'}</td>
                      <td>{new Date(guide.created_at).toLocaleDateString('ja-JP')}</td>
                      <td>
                        <span className={`status-badge ${guide.is_allowed ? 'status-approved' : 'status-pending'}`}>
                          {guide.is_allowed ? '承認済み' : '未承認'}
                        </span>
                      </td>
                      <td>
                        {!guide.is_allowed ? (
                          <button
                            onClick={() => handleGuideApprove(guide.id)}
                            className="btn-primary btn-sm"
                          >
                            承認
                          </button>
                        ) : (
                          <button
                            onClick={() => handleGuideReject(guide.id)}
                            className="btn-secondary btn-sm"
                          >
                            拒否
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      )}
    </div>
  );
};

export default AdminDashboard;

