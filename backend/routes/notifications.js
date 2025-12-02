// 通知関連のルート
const express = require('express');
const pool = require('../database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// 通知一覧取得
router.get('/', authenticateToken, async (req, res) => {
  try {
    const { unread_only } = req.query;
    const userId = req.user.id;

    let query = `SELECT * FROM notifications WHERE user_id = ?`;
    const params = [userId];

    if (unread_only === 'true') {
      query += ' AND read_at IS NULL';
    }

    query += ' ORDER BY created_at DESC LIMIT 50';

    const [notifications] = await pool.execute(query, params);

    res.json({ notifications });
  } catch (error) {
    console.error('通知一覧取得エラー:', error);
    res.status(500).json({ error: '通知一覧の取得中にエラーが発生しました' });
  }
});

// 通知を既読にする
router.put('/:id/read', authenticateToken, async (req, res) => {
  try {
    const notificationId = req.params.id;
    const userId = req.user.id;

    // 通知の存在確認と権限チェック
    const [notifications] = await pool.execute(
      'SELECT id FROM notifications WHERE id = ? AND user_id = ?',
      [notificationId, userId]
    );

    if (notifications.length === 0) {
      return res.status(404).json({ error: '通知が見つかりません' });
    }

    // 既読に更新
    await pool.execute(
      'UPDATE notifications SET read_at = NOW() WHERE id = ?',
      [notificationId]
    );

    res.json({ message: '通知を既読にしました' });
  } catch (error) {
    console.error('通知既読エラー:', error);
    res.status(500).json({ error: '通知既読処理中にエラーが発生しました' });
  }
});

// 未読通知数取得
router.get('/unread-count', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;

    const [result] = await pool.execute(
      'SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL',
      [userId]
    );

    res.json({ unread_count: result[0].count });
  } catch (error) {
    console.error('未読通知数取得エラー:', error);
    res.status(500).json({ error: '未読通知数の取得中にエラーが発生しました' });
  }
});

module.exports = router;

