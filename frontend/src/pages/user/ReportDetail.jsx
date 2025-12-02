// 報告書詳細・承認ページ（ユーザー）
import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import './ReportDetail.css';

const ReportDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(true);
  const [revisionNotes, setRevisionNotes] = useState('');
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    fetchReport();
  }, [id]);

  const fetchReport = async () => {
    try {
      const response = await axios.get(`/reports/user/${id}`);
      setReport(response.data.report);
    } catch (error) {
      console.error('報告書取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!window.confirm('この報告書を承認しますか？')) {
      return;
    }

    setProcessing(true);
    try {
      await axios.post(`/reports/${id}/approve`);
      alert('報告書を承認しました');
      navigate('/');
    } catch (error) {
      alert('承認処理に失敗しました');
      console.error(error);
    } finally {
      setProcessing(false);
    }
  };

  const handleRequestRevision = async () => {
    if (!revisionNotes.trim()) {
      alert('修正内容を入力してください');
      return;
    }

    if (!window.confirm('修正依頼を送信しますか？')) {
      return;
    }

    setProcessing(true);
    try {
      await axios.post(`/reports/${id}/request-revision`, {
        revision_notes: revisionNotes
      });
      alert('修正依頼を送信しました');
      navigate('/');
    } catch (error) {
      alert('修正依頼の送信に失敗しました');
      console.error(error);
    } finally {
      setProcessing(false);
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  if (!report) {
    return <div className="error-message">報告書が見つかりません</div>;
  }

  return (
    <div className="report-detail-container">
      <h1>報告書確認</h1>
      <div className="report-card">
        <div className="report-header">
          <h2>ガイド: {report.guide_name}</h2>
          <span className={`status-badge ${report.status === 'submitted' ? 'status-pending' : 'status-approved'}`}>
            {report.status === 'submitted' ? '承認待ち' : '承認済み'}
          </span>
        </div>

        <div className="report-section">
          <h3>サービス内容</h3>
          <p>{report.service_content || '未記入'}</p>
        </div>

        <div className="report-section">
          <h3>実施日時</h3>
          <p>
            {report.actual_date || '未記入'}
            {report.actual_start_time && report.actual_end_time && (
              <> {report.actual_start_time.substring(0, 5)} - {report.actual_end_time.substring(0, 5)}</>
            )}
          </p>
        </div>

        <div className="report-section">
          <h3>報告欄</h3>
          <p>{report.report_content || '未記入'}</p>
        </div>

        {report.status === 'submitted' && (
          <div className="report-actions">
            <div className="form-group">
              <label htmlFor="revision_notes">修正依頼内容（修正依頼する場合）</label>
              <textarea
                id="revision_notes"
                value={revisionNotes}
                onChange={(e) => setRevisionNotes(e.target.value)}
                rows={4}
                placeholder="修正が必要な点を記入してください"
              />
            </div>
            <div className="action-buttons">
              <button
                onClick={handleApprove}
                className="btn-primary"
                disabled={processing}
                aria-label="報告書を承認"
              >
                {processing ? '処理中...' : '承認'}
              </button>
              <button
                onClick={handleRequestRevision}
                className="btn-secondary"
                disabled={processing || !revisionNotes.trim()}
                aria-label="修正依頼を送信"
              >
                {processing ? '処理中...' : '修正依頼'}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ReportDetail;

