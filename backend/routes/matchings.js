// マッチング関連のルート
const express = require('express');
const pool = require('../database');
const { authenticateToken, requireRole } = require('../middleware/auth');
const { sendNotification } = require('../utils/notifications');

const router = express.Router();

// ガイドが依頼を承諾
router.post('/accept', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const { request_id } = req.body;
    const guideId = req.user.id;

    if (!request_id) {
      return res.status(400).json({ error: '依頼IDを指定してください' });
    }

    // 報告書が未提出の場合は承諾不可
    const [pendingReports] = await pool.execute(
      `SELECT id FROM reports WHERE guide_id = ? AND status IN ('draft', 'submitted')`,
      [guideId]
    );

    if (pendingReports.length > 0) {
      return res.status(403).json({ 
        error: '未提出または承認待ちの報告書があります。報告書を完了してから新しい依頼を承諾してください' 
      });
    }

    // 依頼の存在確認
    const [requests] = await pool.execute(
      'SELECT id, user_id, status FROM requests WHERE id = ?',
      [request_id]
    );

    if (requests.length === 0) {
      return res.status(404).json({ error: '依頼が見つかりません' });
    }

    const request = requests[0];

    // 既に承諾済みかチェック
    const [existingAcceptances] = await pool.execute(
      'SELECT id FROM guide_acceptances WHERE request_id = ? AND guide_id = ?',
      [request_id, guideId]
    );

    if (existingAcceptances.length > 0) {
      return res.status(400).json({ error: 'この依頼は既に承諾済みです' });
    }

    // 承諾レコード作成
    await pool.execute(
      `INSERT INTO guide_acceptances (request_id, guide_id, status, admin_decision)
       VALUES (?, ?, 'pending', 'pending')`,
      [request_id, guideId]
    );

    // 依頼ステータスを更新
    await pool.execute(
      'UPDATE requests SET status = ? WHERE id = ?',
      ['guide_accepted', request_id]
    );

    // 管理者に通知
    const [admins] = await pool.execute(
      'SELECT id FROM users WHERE role = ?',
      ['admin']
    );

    for (const admin of admins) {
      await pool.execute(
        `INSERT INTO notifications (user_id, type, title, message, related_id)
         VALUES (?, 'acceptance', 'ガイドが依頼を承諾しました', 'ガイドが依頼を承諾しました。マッチングを確認してください。', ?)`,
        [admin.id, request_id]
      );
    }

    // 自動マッチング設定を確認
    const [settings] = await pool.execute(
      "SELECT setting_value FROM admin_settings WHERE setting_key = 'auto_matching'"
    );

    const autoMatching = settings.length > 0 && settings[0].setting_value === 'true';

    if (autoMatching) {
      // 自動マッチングの場合は即座にマッチング成立
      await createMatching(request_id, request.user_id, guideId);
      return res.json({ 
        message: '依頼を承諾しました。自動マッチングによりマッチングが成立しました。',
        auto_matched: true
      });
    }

    res.json({ 
      message: '依頼を承諾しました。管理者の承認を待っています。',
      auto_matched: false
    });
  } catch (error) {
    console.error('承諾エラー:', error);
    res.status(500).json({ error: '依頼の承諾中にエラーが発生しました' });
  }
});

// ガイドが依頼を辞退
router.post('/decline', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const { request_id } = req.body;
    const guideId = req.user.id;

    if (!request_id) {
      return res.status(400).json({ error: '依頼IDを指定してください' });
    }

    // 承諾レコードを更新
    await pool.execute(
      `UPDATE guide_acceptances 
       SET status = 'declined' 
       WHERE request_id = ? AND guide_id = ?`,
      [request_id, guideId]
    );

    res.json({ message: '依頼を辞退しました' });
  } catch (error) {
    console.error('辞退エラー:', error);
    res.status(500).json({ error: '依頼の辞退中にエラーが発生しました' });
  }
});

// マッチング作成関数（他のモジュールからも使用可能）
async function createMatching(requestId, userId, guideId) {
  try {
    // マッチングレコード作成
    const [result] = await pool.execute(
      `INSERT INTO matchings (request_id, user_id, guide_id, status)
       VALUES (?, ?, ?, 'matched')`,
      [requestId, userId, guideId]
    );

    const matchingId = result.insertId;

    // 承諾ステータスを更新
    await pool.execute(
      `UPDATE guide_acceptances 
       SET status = 'matched', admin_decision = 'approved'
       WHERE request_id = ? AND guide_id = ?`,
      [requestId, guideId]
    );

    // 依頼ステータスを更新
    await pool.execute(
      'UPDATE requests SET status = ? WHERE id = ?',
      ['matched', requestId]
    );

    // ユーザーとガイドに通知
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'matching', 'マッチングが成立しました', 'マッチングが成立しました。チャットで詳細を確認してください。', ?)`,
      [userId, matchingId]
    );

    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'matching', 'マッチングが成立しました', 'マッチングが成立しました。チャットで詳細を確認してください。', ?)`,
      [guideId, matchingId]
    );

    return matchingId;
  } catch (error) {
    console.error('マッチング作成エラー:', error);
    throw error;
  }
}

// マッチング一覧取得
router.get('/my-matchings', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;
    const userRole = req.user.role;

    let query;
    if (userRole === 'user') {
      query = `SELECT m.*, u.name as guide_name, r.request_type, r.masked_address, r.request_date, r.request_time
               FROM matchings m
               INNER JOIN users u ON m.guide_id = u.id
               INNER JOIN requests r ON m.request_id = r.id
               WHERE m.user_id = ?
               ORDER BY m.matched_at DESC`;
    } else if (userRole === 'guide') {
      query = `SELECT m.*, u.name as user_name, r.request_type, r.masked_address, r.request_date, r.request_time
               FROM matchings m
               INNER JOIN users u ON m.user_id = u.id
               INNER JOIN requests r ON m.request_id = r.id
               WHERE m.guide_id = ?
               ORDER BY m.matched_at DESC`;
    } else {
      return res.status(403).json({ error: 'この操作を実行する権限がありません' });
    }

    const [matchings] = await pool.execute(query, [userId]);

    res.json({ matchings });
  } catch (error) {
    console.error('マッチング一覧取得エラー:', error);
    res.status(500).json({ error: 'マッチング一覧の取得中にエラーが発生しました' });
  }
});

// createMatching関数をエクスポート（admin.jsで使用）
module.exports = router;
module.exports.createMatching = createMatching;

