// 認証関連のルート
const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { body, validationResult } = require('express-validator');
const pool = require('../database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// ユーザー登録
router.post('/register', [
  body('email').isEmail().withMessage('有効なメールアドレスを入力してください'),
  body('password').isLength({ min: 6 }).withMessage('パスワードは6文字以上である必要があります'),
  body('name').notEmpty().withMessage('名前を入力してください'),
  body('role').isIn(['user', 'guide']).withMessage('有効なロールを選択してください')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        error: errors.array().map(e => e.msg).join(', ')
      });
    }

    const { email, password, name, role, phone } = req.body;

    // メールアドレスの重複チェック
    const [existingUsers] = await pool.execute(
      'SELECT id FROM users WHERE email = ?',
      [email]
    );

    if (existingUsers.length > 0) {
      return res.status(400).json({ error: 'このメールアドレスは既に登録されています' });
    }

    // パスワードのハッシュ化
    const passwordHash = await bcrypt.hash(password, 10);

    // ユーザー作成
    const [result] = await pool.execute(
      'INSERT INTO users (email, password_hash, name, phone, role) VALUES (?, ?, ?, ?, ?)',
      [email, passwordHash, name, phone || null, role]
    );

    const userId = result.insertId;

    // プロフィールテーブルに初期レコードを作成
    if (role === 'user') {
      await pool.execute(
        'INSERT INTO user_profiles (user_id) VALUES (?)',
        [userId]
      );
    } else if (role === 'guide') {
      await pool.execute(
        'INSERT INTO guide_profiles (user_id) VALUES (?)',
        [userId]
      );
    }

    // JWT_SECRETの確認
    if (!process.env.JWT_SECRET) {
      console.error('JWT_SECRETが設定されていません');
      return res.status(500).json({ error: 'サーバー設定エラー: JWT_SECRETが設定されていません' });
    }

    // JWTトークン生成
    const token = jwt.sign(
      { id: userId, email, role },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );

    res.status(201).json({
      message: 'ユーザー登録が完了しました',
      token,
      user: {
        id: userId,
        email,
        name,
        role
      }
    });
  } catch (error) {
    console.error('登録エラー:', error);
    console.error('エラー詳細:', error.message);
    console.error('エラーコード:', error.code);
    console.error('スタック:', error.stack);
    
    // JWT_SECRETが設定されていない場合のエラー
    if (error.message && (error.message.includes('secret') || error.message.includes('JWT'))) {
      return res.status(500).json({ error: 'サーバー設定エラー: JWT_SECRETが設定されていません' });
    }
    
    // データベースエラーの場合
    if (error.code) {
      console.error('データベースエラーコード:', error.code);
      if (error.code === 'ER_NO_SUCH_TABLE') {
        return res.status(500).json({ 
          error: 'データベーステーブルが見つかりません。データベースの初期化が必要です。',
          hint: 'database/schema.sqlを実行してください'
        });
      }
      if (error.code === 'ER_BAD_DB_ERROR') {
        return res.status(500).json({ 
          error: 'データベースが見つかりません',
          hint: 'データベースを作成してください'
        });
      }
      if (error.code === 'ECONNREFUSED') {
        return res.status(500).json({ error: 'データベースサーバーに接続できません。MySQLが起動しているか確認してください。' });
      }
      if (error.code === 'ER_ACCESS_DENIED_ERROR') {
        return res.status(500).json({ error: 'データベースへのアクセスが拒否されました。ユーザー名とパスワードを確認してください。' });
      }
      if (error.code === 'ER_DUP_ENTRY') {
        return res.status(400).json({ error: 'このメールアドレスは既に登録されています' });
      }
    }
    
    // より詳細なエラーメッセージを返す（開発環境）
    const errorMessage = process.env.NODE_ENV === 'development' 
      ? `${error.message} (コード: ${error.code || 'N/A'})`
      : 'ユーザー登録中にエラーが発生しました';
    
    res.status(500).json({ 
      error: errorMessage,
      code: error.code || undefined
    });
  }
});

// ログイン
router.post('/login', [
  body('email').isEmail().withMessage('有効なメールアドレスを入力してください'),
  body('password').notEmpty().withMessage('パスワードを入力してください')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, password } = req.body;

    // ユーザー検索
    const [users] = await pool.execute(
      'SELECT id, email, password_hash, name, role, is_allowed FROM users WHERE email = ?',
      [email]
    );

    if (users.length === 0) {
      return res.status(401).json({ error: 'メールアドレスまたはパスワードが正しくありません' });
    }

    const user = users[0];

    if (!user.is_allowed) {
      return res.status(401).json({ error: 'ユーザーは承認されていません' });
    }

    // パスワード検証
    const isValidPassword = await bcrypt.compare(password, user.password_hash);
    if (!isValidPassword) {
      return res.status(401).json({ error: 'メールアドレスまたはパスワードが正しくありません' });
    }

    // JWTトークン生成
    const token = jwt.sign(
      { id: user.id, email: user.email, role: user.role },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );

    res.json({
      message: 'ログインに成功しました',
      token,
      user: {
        id: user.id,
        email: user.email,
        name: user.name,
        role: user.role
      }
    });
  } catch (error) {
    console.error('ログインエラー:', error);
    res.status(500).json({ error: 'ログイン中にエラーが発生しました' });
  }
});

// 現在のユーザー情報取得
router.get('/user', authenticateToken, async (req, res) => {
  try {
    const [users] = await pool.execute(
      'SELECT id, email, name, phone, role, created_at FROM users WHERE id = ?',
      [req.user.id]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'ユーザーが見つかりません' });
    }

    const user = users[0];

    // プロフィール情報も取得
    if (user.role === 'user') {
      const [profiles] = await pool.execute(
        'SELECT contact_method, notes FROM user_profiles WHERE user_id = ?',
        [user.id]
      );
      user.profile = profiles[0] || {};
    } else if (user.role === 'guide') {
      const [profiles] = await pool.execute(
        'SELECT introduction, available_areas, available_days, available_times FROM guide_profiles WHERE user_id = ?',
        [user.id]
      );
      user.profile = profiles[0] || {};
      if (user.profile.available_areas) {
        user.profile.available_areas = JSON.parse(user.profile.available_areas);
      }
      if (user.profile.available_days) {
        user.profile.available_days = JSON.parse(user.profile.available_days);
      }
      if (user.profile.available_times) {
        user.profile.available_times = JSON.parse(user.profile.available_times);
      }
    }

    res.json({ user });
  } catch (error) {
    console.error('ユーザー情報取得エラー:', error);
    res.status(500).json({ error: 'ユーザー情報の取得中にエラーが発生しました' });
  }
});

module.exports = router;

