// プロフィールページ
import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';
import './Profile.css';

const Profile = () => {
  const { user, updateUser, isGuide } = useAuth();
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    contact_method: '',
    notes: '',
    introduction: '',
    available_areas: [],
    available_days: [],
    available_times: []
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await axios.get('/auth/me');
      const userData = response.data.user;
      setFormData({
        name: userData.name || '',
        phone: userData.phone || '',
        contact_method: userData.profile?.contact_method || '',
        notes: userData.profile?.notes || '',
        introduction: userData.profile?.introduction || '',
        available_areas: userData.profile?.available_areas || [],
        available_days: userData.profile?.available_days || [],
        available_times: userData.profile?.available_times || []
      });
    } catch (error) {
      console.error('プロフィール取得エラー:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleArrayChange = (name, value, checked) => {
    setFormData(prev => {
      const currentArray = prev[name] || [];
      if (checked) {
        return { ...prev, [name]: [...currentArray, value] };
      } else {
        return { ...prev, [name]: currentArray.filter(item => item !== value) };
      }
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage('');

    try {
      if (isGuide) {
        await axios.put('/users/guide-profile', {
          introduction: formData.introduction,
          available_areas: formData.available_areas,
          available_days: formData.available_days,
          available_times: formData.available_times
        });
      }
      await axios.put('/users/profile', {
        name: formData.name,
        phone: formData.phone,
        contact_method: formData.contact_method,
        notes: formData.notes
      });
      updateUser({ name: formData.name });
      setMessage('プロフィールが更新されました');
    } catch (error) {
      const errorMessage = error.response?.data?.error || 
                          (error.response?.data?.errors && Array.isArray(error.response.data.errors) 
                            ? error.response.data.errors.map(e => e.msg).join(', ')
                            : '') ||
                          'プロフィールの更新に失敗しました';
      setMessage(errorMessage);
      console.error('プロフィール更新エラー:', error.response?.data || error.message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="loading-container">読み込み中...</div>;
  }

  return (
    <div className="profile-container">
      <h1>プロフィール編集</h1>
      <form onSubmit={handleSubmit} className="profile-form" aria-label="プロフィール編集フォーム">
        {message && (
          <div className={`message ${message.includes('失敗') ? 'error-message' : 'success-message'}`} role="alert">
            {message}
          </div>
        )}

        <div className="form-group">
          <label htmlFor="name">お名前 <span className="required">*</span></label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            required
            aria-required="true"
          />
        </div>

        <div className="form-group">
          <label htmlFor="phone">電話番号</label>
          <input
            type="tel"
            id="phone"
            name="phone"
            value={formData.phone}
            onChange={handleChange}
          />
        </div>

        {!isGuide && (
          <>
            <div className="form-group">
              <label htmlFor="contact_method">連絡手段</label>
              <input
                type="text"
                id="contact_method"
                name="contact_method"
                value={formData.contact_method}
                onChange={handleChange}
                placeholder="例: 電話、メール、LINE等"
              />
            </div>
            <div className="form-group">
              <label htmlFor="notes">備考</label>
              <textarea
                id="notes"
                name="notes"
                value={formData.notes}
                onChange={handleChange}
                rows={4}
              />
            </div>
          </>
        )}

        {isGuide && (
          <>
            <div className="form-group">
              <label htmlFor="introduction">自己紹介</label>
              <textarea
                id="introduction"
                name="introduction"
                value={formData.introduction}
                onChange={handleChange}
                rows={4}
                placeholder="自己紹介を記入してください"
              />
            </div>

            <div className="form-group">
              <label>対応可能エリア</label>
              <div className="checkbox-group">
                {['東京都', '大阪府', '京都府', '神奈川県', '埼玉県', '千葉県', '愛知県', 'その他'].map(area => (
                  <label key={area} className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={formData.available_areas.includes(area)}
                      onChange={(e) => handleArrayChange('available_areas', area, e.target.checked)}
                    />
                    {area}
                  </label>
                ))}
              </div>
            </div>

            <div className="form-group">
              <label>対応可能日</label>
              <div className="checkbox-group">
                {['平日', '土日', '祝日'].map(day => (
                  <label key={day} className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={formData.available_days.includes(day)}
                      onChange={(e) => handleArrayChange('available_days', day, e.target.checked)}
                    />
                    {day}
                  </label>
                ))}
              </div>
            </div>

            <div className="form-group">
              <label>対応可能時間帯</label>
              <div className="checkbox-group">
                {['午前', '午後', '夜間'].map(time => (
                  <label key={time} className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={formData.available_times.includes(time)}
                      onChange={(e) => handleArrayChange('available_times', time, e.target.checked)}
                    />
                    {time}
                  </label>
                ))}
              </div>
            </div>
          </>
        )}

        <div className="form-actions">
          <button
            type="submit"
            className="btn-primary"
            disabled={saving}
            aria-label="プロフィールを保存"
          >
            {saving ? '保存中...' : '保存'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default Profile;

