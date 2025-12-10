// マッチング詳細ページ
import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';
import './MatchingDetail.css';

const MatchingDetail = () => {
  const { id } = useParams();
  const { user, isUser, isGuide } = useAuth();
  const [matching, setMatching] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchMatching();
  }, [id]);

  const fetchMatching = async () => {
    try {
      const response = await axios.get('/matchings/my-matchings');
      const match = response.data.matchings.find(m => m.id === parseInt(id));
      setMatching(match);
    } catch (error) {
      console.error('マッチング取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>読み込み中...</p>
      </div>
    );
  }

  if (!matching) {
    return <div className="error-message">マッチングが見つかりません</div>;
  }

  return (
    <div className="matching-detail-container">
      <h1>マッチング詳細</h1>
      <div className="matching-card">
        <div className="matching-info">
          <h2>依頼情報</h2>
          <p><strong>タイプ:</strong> {matching.request_type}</p>
          <p><strong>場所:</strong> {matching.masked_address}</p>
          <p><strong>日時:</strong> {matching.request_date} {matching.request_time}</p>
        </div>
        <div className="matching-participants">
          {isUser && (
            <div>
              <h3>ガイド</h3>
              <p>{matching.guide_name}</p>
            </div>
          )}
          {isGuide && (
            <div>
              <h3>ユーザー</h3>
              <p>{matching.user_name}</p>
            </div>
          )}
        </div>
        <div className="matching-actions">
          <Link
            to={`/chat/${matching.id}`}
            className="btn-primary btn-with-icon"
            aria-label="チャットを開く"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>チャットを開く</span>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default MatchingDetail;

