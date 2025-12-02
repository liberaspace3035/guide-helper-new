// チャット関連のルート
const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// 未読メッセージ数取得（パラメータ付きルートの前に定義する必要がある）
router.get('/unread-count', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;

    // ユーザーが参加しているマッチングのIDを取得
    const [matchings] = await pool.execute(
      'SELECT id FROM matchings WHERE user_id = ? OR guide_id = ?',
      [userId, userId]
    );

    if (matchings.length === 0) {
      return res.json({ unread_count: 0 });
    }

    const matchingIds = matchings.map(m => m.id);

    // 自分が送信していないメッセージの数をカウント
    // より正確には、各マッチングで最後に開いた時刻を記録する必要があるが、
    // シンプルに「自分が送信していないメッセージ」を未読として扱う
    const placeholders = matchingIds.map(() => '?').join(',');
    const [result] = await pool.execute(
      `SELECT COUNT(*) as count
       FROM chat_messages
       WHERE matching_id IN (${placeholders})
       AND sender_id != ?`,
      [...matchingIds, userId]
    );

    res.json({ unread_count: result[0].count });
  } catch (error) {
    console.error('未読メッセージ数取得エラー:', error);
    res.status(500).json({ error: '未読メッセージ数の取得中にエラーが発生しました' });
  }
});

// チャットメッセージ送信
router.post('/messages', authenticateToken, [
  body('matching_id').isInt().withMessage('マッチングIDを指定してください'),
  body('message').notEmpty().withMessage('メッセージを入力してください')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { matching_id, message } = req.body;
    const senderId = req.user.id;

    // マッチングの存在確認と権限チェック
    const [matchings] = await pool.execute(
      'SELECT user_id, guide_id FROM matchings WHERE id = ?',
      [matching_id]
    );

    if (matchings.length === 0) {
      return res.status(404).json({ error: 'マッチングが見つかりません' });
    }

    const matching = matchings[0];
    if (matching.user_id !== senderId && matching.guide_id !== senderId) {
      return res.status(403).json({ error: 'このマッチングのチャットにアクセスする権限がありません' });
    }

    // メッセージ保存
    const [result] = await pool.execute(
      'INSERT INTO chat_messages (matching_id, sender_id, message) VALUES (?, ?, ?)',
      [matching_id, senderId, message]
    );

    res.status(201).json({
      message: 'メッセージが送信されました',
      chat_message: {
        id: result.insertId,
        matching_id,
        sender_id: senderId,
        message,
        created_at: new Date()
      }
    });
  } catch (error) {
    console.error('メッセージ送信エラー:', error);
    res.status(500).json({ error: 'メッセージ送信中にエラーが発生しました' });
  }
});

// チャットメッセージ一覧取得
router.get('/messages/:matching_id', authenticateToken, async (req, res) => {
  try {
    const matchingId = req.params.matching_id;
    const userId = req.user.id;

    // マッチングの存在確認と権限チェック
    const [matchings] = await pool.execute(
      'SELECT user_id, guide_id FROM matchings WHERE id = ?',
      [matchingId]
    );

    if (matchings.length === 0) {
      return res.status(404).json({ error: 'マッチングが見つかりません' });
    }

    const matching = matchings[0];
    if (matching.user_id !== userId && matching.guide_id !== userId) {
      return res.status(403).json({ error: 'このマッチングのチャットにアクセスする権限がありません' });
    }

    // メッセージ取得
    const [messages] = await pool.execute(
      `SELECT cm.*, u.name as sender_name, u.role as sender_role
       FROM chat_messages cm
       INNER JOIN users u ON cm.sender_id = u.id
       WHERE cm.matching_id = ?
       ORDER BY cm.created_at ASC`,
      [matchingId]
    );

    res.json({ messages });
  } catch (error) {
    console.error('メッセージ取得エラー:', error);
    res.status(500).json({ error: 'メッセージの取得中にエラーが発生しました' });
  }
});

module.exports = router;

