// 依頼一覧ページ（ガイド）
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import './Requests.css';

const GuideRequests = () => {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchRequests();
  }, []);

  const fetchRequests = async () => {
    try {
      const response = await axios.get('/requests/guide/available');
      setRequests(response.data.requests);
    } catch (err) {
      setError('依頼一覧の取得に失敗しました');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleAccept = async (requestId) => {
    if (!window.confirm('この依頼を承諾しますか？')) {
      return;
    }

    try {
      await axios.post('/matchings/accept', { request_id: requestId });
      alert('依頼を承諾しました');
      fetchRequests(); // 一覧を更新
    } catch (err) {
      alert(err.response?.data?.error || '承諾に失敗しました');
    }
  };

  const handleDecline = async (requestId) => {
    if (!window.confirm('この依頼を辞退しますか？')) {
      return;
    }

    try {
      await axios.post('/matchings/decline', { request_id: requestId });
      alert('依頼を辞退しました');
      fetchRequests(); // 一覧を更新
    } catch (err) {
      alert(err.response?.data?.error || '辞退に失敗しました');
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  if (error) {
    return <div className="error-message">{error}</div>;
  }

  return (
    <div className="requests-container">
      <h1>依頼一覧</h1>
      <p className="info-text">個人情報は保護されています。条件に合わない依頼も承諾できます。</p>

      {requests.length === 0 ? (
        <div className="empty-state">
          <p>現在、利用可能な依頼はありません</p>
        </div>
      ) : (
        <div className="requests-list">
          {requests.map(request => (
            <div key={request.id} className="request-card">
              <div className="request-header">
                <h3>{request.request_type}</h3>
                <span className={`status-badge ${request.status === 'pending' ? 'status-pending' : 'status-accepted'}`}>
                  {request.status === 'pending' ? '承諾待ち' : '承諾済み'}
                </span>
              </div>
              <div className="request-details">
                <p><strong>場所:</strong> {request.masked_address}</p>
                <p><strong>日時:</strong> {request.request_date} {request.request_time}</p>
                <p><strong>内容:</strong> {request.service_content}</p>
                <p><strong>作成日:</strong> {new Date(request.created_at).toLocaleString('ja-JP')}</p>
              </div>
              <div className="request-actions">
                <button
                  onClick={() => handleAccept(request.id)}
                  className="btn-primary"
                  aria-label="依頼を承諾"
                >
                  承諾
                </button>
                <button
                  onClick={() => handleDecline(request.id)}
                  className="btn-secondary"
                  aria-label="依頼を辞退"
                >
                  辞退
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default GuideRequests;

