// データベース接続プール
const mysql = require('mysql2/promise');
const dotenv = require('dotenv');

// 環境変数の読み込み
dotenv.config();

// データベース接続プールを作成
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'guide_matching_db',
  port: process.env.DB_PORT || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

module.exports = pool;


