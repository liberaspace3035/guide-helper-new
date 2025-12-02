// メインサーバーファイル
const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');

// 環境変数の読み込み
dotenv.config();

// 必須環境変数のチェック
if (!process.env.JWT_SECRET) {
  console.warn('警告: JWT_SECRETが設定されていません。.envファイルにJWT_SECRETを設定してください。');
  console.warn('開発環境ではデフォルト値を使用しますが、本番環境では必ず設定してください。');
  // 開発環境でのみデフォルト値を設定（本番では使用しない）
  if (process.env.NODE_ENV !== 'production') {
    process.env.JWT_SECRET = 'dev-secret-key-change-in-production-' + Date.now();
    console.warn('開発用の一時的なJWT_SECRETが設定されました。');
  }
}

const app = express();
const PORT = process.env.PORT || 3001;

// ミドルウェア設定
app.use(cors({
  origin: process.env.FRONTEND_URL || 'http://localhost:5173',
  credentials: true
}));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// MySQL接続設定
const mysql = require('mysql2/promise');

const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'guide_matching_db',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

const pool = mysql.createPool(dbConfig);

// MySQL接続テスト (サーバー起動時)
pool.getConnection()
  .then(connection => {
    console.log('MySQLデータベースに正常に接続されました');
    connection.release();
  })
  .catch(err => {
    console.error('MySQLデータベースへの接続に失敗しました:', err);
    process.exit(1); // DB接続失敗時はサーバーを停止
  });

// 他のモジュールで使えるようにエクスポート
module.exports.db = pool;

// ルート設定
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const requestRoutes = require('./routes/requests');
const matchingRoutes = require('./routes/matchings');
const chatRoutes = require('./routes/chat');
const reportRoutes = require('./routes/reports');
const adminRoutes = require('./routes/admin');
const notificationRoutes = require('./routes/notifications');
// ルート登録
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/requests', requestRoutes);
app.use('/api/matchings', matchingRoutes);
app.use('/api/chat', chatRoutes);
app.use('/api/reports', reportRoutes);
app.use('/api/admin', adminRoutes);
app.use('/api/notifications', notificationRoutes);

// ヘルスチェックエンドポイント
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', message: 'サーバーは正常に動作しています' });
});

// 404ハンドラー（存在しないルート）
app.use('/api/*', (req, res) => {
  console.error(`404エラー: ルートが見つかりません - ${req.method} ${req.originalUrl}`);
  res.status(404).json({ 
    error: 'ルートが見つかりません',
    path: req.originalUrl,
    method: req.method
  });
});

// エラーハンドリングミドルウェア
app.use((err, req, res, next) => {
  console.error('エラー:', err);
  res.status(err.status || 500).json({
    error: err.message || 'サーバー内部エラーが発生しました'
  });
});

// サーバー起動
app.listen(PORT, () => {
  console.log(`サーバーがポート ${PORT} で起動しました`);
});

// アプリケーションをエクスポート
module.exports = { app };

