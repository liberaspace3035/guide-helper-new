// ログインページ
import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import './Login.css';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const { showSuccess, showError } = useToast();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    const result = await login(email, password);

    if (result.success) {
      showSuccess('ログインに成功しました');
      if (result.user?.role === 'admin') {
        setTimeout(() => {
          navigate('/admin');
        }, 500);
      } else {
        setTimeout(() => {
          navigate('/');
        }, 500);
      }
    } else {
      setError(result.error);
      showError(result.error);
    }

    setLoading(false);
  };

  return (
    <div className="login-container">
      <div className="login-card">
        <h1>ログイン</h1>
        <form onSubmit={handleSubmit} aria-label="ログインフォーム">
          {error && (
            <div className="error-message" role="alert" aria-live="polite">
              {error}
            </div>
          )}
          <div className="form-group">
            <label htmlFor="email">メールアドレス</label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
              aria-required="true"
            />
          </div>
          <div className="form-group">
            <label htmlFor="password">パスワード</label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
              aria-required="true"
            />
          </div>
          <button
            type="submit"
            className="btn-primary btn-block"
            disabled={loading}
            aria-label="ログインボタン"
          >
            {loading ? 'ログイン中...' : 'ログイン'}
          </button>
        </form>
        <p className="register-link">
          アカウントをお持ちでない方は{' '}
          <Link to="/register">こちらから登録</Link>
        </p>
      </div>
    </div>
  );
};

export default Login;

