// 報告書作成・編集フォーム（ガイド）
import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import './ReportForm.css';

const ReportForm = () => {
  const { id, matchingId } = useParams();
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    service_content: '',
    report_content: '',
    actual_date: '',
    actual_start_time: '',
    actual_end_time: ''
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (id) {
      fetchReport();
    } else if (matchingId) {
      // 新規作成の場合はマッチング情報を取得
      fetchMatchingInfo();
    }
  }, [id, matchingId]);

  const fetchReport = async () => {
    try {
      const response = await axios.get(`/reports/${id}`);
      const report = response.data.report;
      setFormData({
        service_content: report.service_content || '',
        report_content: report.report_content || '',
        actual_date: report.actual_date || '',
        actual_start_time: report.actual_start_time ? report.actual_start_time.substring(0, 5) : '',
        actual_end_time: report.actual_end_time ? report.actual_end_time.substring(0, 5) : ''
      });
    } catch (error) {
      setError('報告書の取得に失敗しました');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const fetchMatchingInfo = async () => {
    try {
      const response = await axios.get(`/matchings/my-matchings`);
      const matching = response.data.matchings.find(m => m.id === parseInt(matchingId));
      if (matching) {
        // 依頼情報から初期値を設定
        setFormData(prev => ({
          ...prev,
          actual_date: matching.request_date || ''
        }));
      }
    } catch (error) {
      console.error('マッチング情報取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSave = async () => {
    setSaving(true);
    setError('');

    try {
      const payload = {
        matching_id: id ? undefined : parseInt(matchingId),
        ...formData
      };

      if (id) {
        // 既存の報告書を更新
        await axios.post(`/reports/${id}`, payload);
      } else {
        // 新規作成
        await axios.post('/reports', payload);
      }

      alert('報告書が保存されました');
      navigate('/guide/reports');
    } catch (err) {
      setError(err.response?.data?.error || '報告書の保存に失敗しました');
    } finally {
      setSaving(false);
    }
  };

  const handleSubmit = async () => {
    if (!window.confirm('報告書を提出しますか？提出後はユーザーの承認が必要です。')) {
      return;
    }

    setSaving(true);
    setError('');

    try {
      // まず保存
      await handleSave();
      // その後提出
      await axios.post(`/reports/${id || 'new'}/submit`);
      alert('報告書が提出されました');
      navigate('/guide/reports');
    } catch (err) {
      setError(err.response?.data?.error || '報告書の提出に失敗しました');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  return (
    <div className="report-form-container">
      <h1>{id ? '報告書編集' : '報告書作成'}</h1>
      {error && (
        <div className="error-message" role="alert">
          {error}
        </div>
      )}
      <form className="report-form" aria-label="報告書フォーム">
        <div className="form-group">
          <label htmlFor="service_content">サービス内容</label>
          <textarea
            id="service_content"
            name="service_content"
            value={formData.service_content}
            onChange={handleChange}
            rows={6}
            placeholder="実施したサービス内容を記入してください"
          />
        </div>

        <div className="form-row">
          <div className="form-group">
            <label htmlFor="actual_date">実施日</label>
            <input
              type="date"
              id="actual_date"
              name="actual_date"
              value={formData.actual_date}
              onChange={handleChange}
            />
          </div>
          <div className="form-group">
            <label htmlFor="actual_start_time">開始時刻</label>
            <input
              type="time"
              id="actual_start_time"
              name="actual_start_time"
              value={formData.actual_start_time}
              onChange={handleChange}
            />
          </div>
          <div className="form-group">
            <label htmlFor="actual_end_time">終了時刻</label>
            <input
              type="time"
              id="actual_end_time"
              name="actual_end_time"
              value={formData.actual_end_time}
              onChange={handleChange}
            />
          </div>
        </div>

        <div className="form-group">
          <label htmlFor="report_content">報告欄（自由記入）</label>
          <textarea
            id="report_content"
            name="report_content"
            value={formData.report_content}
            onChange={handleChange}
            rows={8}
            placeholder="実施内容の詳細、気づいた点、改善点などを自由に記入してください"
          />
        </div>

        <div className="form-actions">
          <button
            type="button"
            onClick={handleSave}
            className="btn-secondary"
            disabled={saving}
            aria-label="下書き保存"
          >
            {saving ? '保存中...' : '下書き保存'}
          </button>
          <button
            type="button"
            onClick={handleSubmit}
            className="btn-primary"
            disabled={saving}
            aria-label="報告書を提出"
          >
            {saving ? '提出中...' : '報告書を提出'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ReportForm;

