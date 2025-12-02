// 依頼一覧ページ（ユーザー）
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import './Requests.css';

const Requests = () => {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchRequests();
  }, []);

  const fetchRequests = async () => {
    try {
      const response = await axios.get('/requests/my-requests');
      setRequests(response.data.requests);
    } catch (err) {
      setError('依頼一覧の取得に失敗しました');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const getStatusLabel = (status) => {
    const statusMap = {
      pending: '承諾待ち',
      guide_accepted: 'ガイド承諾済み',
      matched: 'マッチング成立',
      in_progress: '進行中',
      completed: '完了',
      cancelled: 'キャンセル'
    };
    return statusMap[status] || status;
  };

  const getStatusClass = (status) => {
    const classMap = {
      pending: 'status-pending',
      guide_accepted: 'status-accepted',
      matched: 'status-matched',
      in_progress: 'status-in-progress',
      completed: 'status-completed',
      cancelled: 'status-cancelled'
    };
    return classMap[status] || '';
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  if (error) {
    return <div className="error-message">{error}</div>;
  }

  return (
    <div className="requests-container">
      <div className="page-header">
        <h1>依頼一覧</h1>
        <Link to="/requests/new" className="btn-primary-icon">
          新しい依頼を作成
        </Link>
      </div>

      {requests.length === 0 ? (
        <div className="empty-state">
          <p>依頼がありません</p>
          <Link to="/requests/new" className="btn-primary">
            最初の依頼を作成
          </Link>
        </div>
      ) : (
        <div className="requests-list">
          {requests.map(request => (
            <div key={request.id} className="request-card">
              <div className="request-header">
                <h3>{request.request_type}</h3>
                <span className={`status-badge ${getStatusClass(request.status)}`}>
                  {getStatusLabel(request.status)}
                </span>
              </div>
              <div className="request-details">
                <p><strong>場所:</strong> {request.masked_address}</p>
                <p><strong>日時:</strong> {request.request_date} {request.request_time}</p>
                <p><strong>内容:</strong> {request.service_content}</p>
                <p><strong>作成日:</strong> {new Date(request.created_at).toLocaleString('ja-JP')}</p>
              </div>
              <div className="request-actions">
                {request.status === 'matched' && request.matching_id && (
                  <>
                    <Link
                      to={`/chat/${request.matching_id}`}
                      className="btn-primary btn-with-icon"
                      aria-label="チャットを開く"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                      </svg>
                      <span>チャットを開く</span>
                    </Link>
                    <Link
                      to={`/matchings/${request.matching_id}`}
                      className="btn-secondary btn-with-icon"
                      aria-label="マッチング詳細を確認"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                      </svg>
                      <span>詳細を確認</span>
                    </Link>
                  </>
                )}
                {request.status === 'guide_accepted' && (
                  <p className="info-text" style={{ color: 'var(--text-secondary)', fontSize: '14px', margin: 0 }}>
                    ガイドが承諾しました。管理者の承認を待っています。
                  </p>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default Requests;

