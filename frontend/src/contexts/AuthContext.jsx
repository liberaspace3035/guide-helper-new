// 認証コンテキスト
import React, { createContext, useState, useContext, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuthはAuthProvider内で使用する必要があります');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(localStorage.getItem('token'));

  // APIのベースURL設定
  // ngrok使用時は環境変数で設定、または自動検出
  const getApiBaseURL = () => {
    // 環境変数で明示的に設定されている場合
    if (import.meta.env.VITE_API_URL) {
      return import.meta.env.VITE_API_URL.endsWith('/api') 
        ? import.meta.env.VITE_API_URL 
        : `${import.meta.env.VITE_API_URL}/api`;
    }
    
    // ngrok使用時: 現在のホストから自動検出（開発環境のみ）
    if (import.meta.env.DEV && window.location.hostname.includes('ngrok')) {
      // ngrok URLの場合、同じドメインでAPIを呼び出す（Vite proxy経由）
      return '/api';
    }
    
    // デフォルト: ローカル開発環境
    return 'http://localhost:3001/api';
  };
  
  axios.defaults.baseURL = getApiBaseURL();

  // トークンが変更されたらaxiosのデフォルトヘッダーを更新
  useEffect(() => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem('token', token);
    } else {
      delete axios.defaults.headers.common['Authorization'];
      localStorage.removeItem('token');
    }
  }, [token]);

  // 初期ロード時にユーザー情報を取得
  useEffect(() => {
    if (token) {
      fetchUser();
    } else {
      setLoading(false);
    }
  }, [token]);

  const fetchUser = async () => {
    try {
      const response = await axios.get('/auth/me');
      setUser(response.data.user);
    } catch (error) {
      console.error('ユーザー情報取得エラー:', error);
      setToken(null);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    try {
      const response = await axios.post('/auth/login', { email, password });
      setToken(response.data.token);
      setUser(response.data.user);
      return { success: true, user: response.data.user };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.error || 'ログインに失敗しました'
      };
    }
  };

  const register = async (userData) => {
    try {
      const url = '/auth/register';
      const fullUrl = `${axios.defaults.baseURL}${url}`;
      console.log('登録リクエスト送信:', { url, fullUrl, baseURL: axios.defaults.baseURL, userData });
      
      const response = await axios.post(url, userData);
      setToken(response.data.token);
      setUser(response.data.user);
      return { success: true };
    } catch (error) {
      console.error('登録エラー詳細:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status,
        statusText: error.response?.statusText,
        url: error.config?.url,
        baseURL: error.config?.baseURL,
        fullURL: error.config?.baseURL + error.config?.url
      });
      return {
        success: false,
        error: error.response?.data?.error || error.response?.data?.errors || error.message || 'ユーザー登録中にエラーが発生しました'
      };
    }
  };

  const logout = () => {
    setToken(null);
    setUser(null);
  };

  const updateUser = (userData) => {
    setUser(prev => ({ ...prev, ...userData }));
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        register,
        logout,
        updateUser,
        isAuthenticated: !!user,
        isUser: user?.role === 'user',
        isGuide: user?.role === 'guide',
        isAdmin: user?.role === 'admin'
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

