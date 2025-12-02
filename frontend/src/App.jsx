// メインアプリケーションコンポーネント
import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ToastProvider } from './contexts/ToastContext';
import PrivateRoute from './components/PrivateRoute';
import Layout from './components/Layout';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import UserRequestForm from './pages/user/RequestForm';
import UserRequests from './pages/user/Requests';
import GuideRequests from './pages/guide/Requests';
import MatchingDetail from './pages/MatchingDetail';
import Chat from './pages/Chat';
import ReportForm from './pages/guide/ReportForm';
import ReportDetail from './pages/user/ReportDetail';
import AdminDashboard from './pages/admin/Dashboard';
import Profile from './pages/Profile';

function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <Routes>
        {/* 公開ルート */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        {/* 認証が必要なルート */}
        <Route element={<Layout />}>
          <Route path="/" element={<PrivateRoute><Dashboard /></PrivateRoute>} />
          <Route path="/profile" element={<PrivateRoute><Profile /></PrivateRoute>} />
          
          {/* ユーザー専用ルート */}
          <Route path="/requests/new" element={<PrivateRoute requiredRole="user"><UserRequestForm /></PrivateRoute>} />
          <Route path="/requests" element={<PrivateRoute requiredRole="user"><UserRequests /></PrivateRoute>} />
          <Route path="/reports/:id" element={<PrivateRoute requiredRole="user"><ReportDetail /></PrivateRoute>} />
          
          {/* ガイド専用ルート */}
          <Route path="/guide/requests" element={<PrivateRoute requiredRole="guide"><GuideRequests /></PrivateRoute>} />
          <Route path="/guide/reports/new/:matchingId" element={<PrivateRoute requiredRole="guide"><ReportForm /></PrivateRoute>} />
          <Route path="/guide/reports/:id" element={<PrivateRoute requiredRole="guide"><ReportForm /></PrivateRoute>} />
          
          {/* 共通ルート */}
          <Route path="/matchings/:id" element={<PrivateRoute><MatchingDetail /></PrivateRoute>} />
          <Route path="/chat/:matchingId" element={<PrivateRoute><Chat /></PrivateRoute>} />
          
          {/* 管理者専用ルート */}
          <Route path="/admin" element={<PrivateRoute requiredRole="admin"><AdminDashboard /></PrivateRoute>} />
        </Route>

        {/* デフォルトリダイレクト */}
        <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </ToastProvider>
  );
}

export default App;

