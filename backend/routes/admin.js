// 管理者関連のルート
const express = require('express');
const pool = require('../database');
const { authenticateToken, requireRole } = require('../middleware/auth');
const { createMatching } = require('../routes/matchings');

const router = express.Router();

// 全ての管理者ルートで認証と管理者権限を要求
router.use(authenticateToken);
router.use(requireRole('admin'));

// 依頼一覧取得
router.get('/requests', async (req, res) => {
  try {
    const [requests] = await pool.execute(
      `SELECT r.*, u.name as user_name, u.email as user_email
       FROM requests r
       INNER JOIN users u ON r.user_id = u.id
       ORDER BY r.created_at DESC`
    );

    res.json({ requests });
  } catch (error) {
    console.error('依頼一覧取得エラー:', error);
    res.status(500).json({ error: '依頼一覧の取得中にエラーが発生しました' });
  }
});

// 承諾一覧取得
router.get('/acceptances', async (req, res) => {
  try {
    const [acceptances] = await pool.execute(
      `SELECT ga.*, r.request_type, r.masked_address, r.request_date, r.request_time,
              u.name as user_name, g.name as guide_name
       FROM guide_acceptances ga
       INNER JOIN requests r ON ga.request_id = r.id
       INNER JOIN users u ON r.user_id = u.id
       INNER JOIN users g ON ga.guide_id = g.id
       WHERE ga.status = 'pending'
       ORDER BY ga.created_at DESC`
    );

    res.json({ acceptances });
  } catch (error) {
    console.error('承諾一覧取得エラー:', error);
    res.status(500).json({ error: '承諾一覧の取得中にエラーが発生しました' });
  }
});

// マッチング手動承認
router.post('/matchings/approve', async (req, res) => {
  try {
    const { request_id, guide_id } = req.body;

    if (!request_id || !guide_id) {
      return res.status(400).json({ error: '依頼IDとガイドIDを指定してください' });
    }

    // 依頼情報取得
    const [requests] = await pool.execute(
      'SELECT user_id FROM requests WHERE id = ?',
      [request_id]
    );

    if (requests.length === 0) {
      return res.status(404).json({ error: '依頼が見つかりません' });
    }

    const userId = requests[0].user_id;

    // マッチング作成
    const matchingId = await createMatching(request_id, userId, guide_id);

    res.json({
      message: 'マッチングが承認されました',
      matching_id: matchingId
    });
  } catch (error) {
    console.error('マッチング承認エラー:', error);
    res.status(500).json({ error: 'マッチング承認中にエラーが発生しました' });
  }
});

// マッチング手動却下
router.post('/matchings/reject', async (req, res) => {
  try {
    const { request_id, guide_id } = req.body;

    if (!request_id || !guide_id) {
      return res.status(400).json({ error: '依頼IDとガイドIDを指定してください' });
    }

    // 承諾ステータスを更新
    await pool.execute(
      `UPDATE guide_acceptances 
       SET status = 'rejected', admin_decision = 'rejected'
       WHERE request_id = ? AND guide_id = ?`,
      [request_id, guide_id]
    );

    // 依頼ステータスをpendingに戻す（他のガイドの承諾を待つ）
    await pool.execute(
      'UPDATE requests SET status = ? WHERE id = ?',
      ['pending', request_id]
    );

    res.json({ message: 'マッチングが却下されました' });
  } catch (error) {
    console.error('マッチング却下エラー:', error);
    res.status(500).json({ error: 'マッチング却下中にエラーが発生しました' });
  }
});

// 自動マッチング設定取得
router.get('/settings/auto-matching', async (req, res) => {
  try {
    const [settings] = await pool.execute(
      "SELECT setting_value FROM admin_settings WHERE setting_key = 'auto_matching'"
    );

    const autoMatching = settings.length > 0 && settings[0].setting_value === 'true';

    res.json({ auto_matching: autoMatching });
  } catch (error) {
    console.error('設定取得エラー:', error);
    res.status(500).json({ error: '設定の取得中にエラーが発生しました' });
  }
});

// 自動マッチング設定更新
router.put('/settings/auto-matching', async (req, res) => {
  try {
    const { auto_matching } = req.body;

    if (typeof auto_matching !== 'boolean') {
      return res.status(400).json({ error: 'auto_matchingは真偽値である必要があります' });
    }

    await pool.execute(
      `UPDATE admin_settings SET setting_value = ? WHERE setting_key = 'auto_matching'`,
      [auto_matching ? 'true' : 'false']
    );

    res.json({
      message: '自動マッチング設定が更新されました',
      auto_matching
    });
  } catch (error) {
    console.error('設定更新エラー:', error);
    res.status(500).json({ error: '設定更新中にエラーが発生しました' });
  }
});

// 報告書一覧取得
router.get('/reports', async (req, res) => {
  try {
    const [reports] = await pool.execute(
      `SELECT r.*, u.name as user_name, g.name as guide_name, 
              req.request_type, req.request_date
       FROM reports r
       INNER JOIN users u ON r.user_id = u.id
       INNER JOIN users g ON r.guide_id = g.id
       INNER JOIN requests req ON r.request_id = req.id
       ORDER BY r.created_at DESC`
    );

    res.json({ reports });
  } catch (error) {
    console.error('報告書一覧取得エラー:', error);
    res.status(500).json({ error: '報告書一覧の取得中にエラーが発生しました' });
  }
});

// CSV出力（報告書一覧）
router.get('/reports/csv', async (req, res) => {
  try {
    const [reports] = await pool.execute(
      `SELECT r.id, r.actual_date, r.actual_start_time, r.actual_end_time,
              u.name as user_name, u.email as user_email,
              g.name as guide_name, g.email as guide_email,
              req.request_type, req.request_date,
              r.status, r.approved_at
       FROM reports r
       INNER JOIN users u ON r.user_id = u.id
       INNER JOIN users g ON r.guide_id = g.id
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.status = 'approved'
       ORDER BY r.approved_at DESC`
    );

    // CSV形式に変換
    const csvHeader = 'ID,利用日,開始時刻,終了時刻,ユーザー名,ユーザーメール,ガイド名,ガイドメール,依頼タイプ,依頼日,承認日時\n';
    const csvRows = reports.map(r => {
      const startTime = r.actual_start_time ? r.actual_start_time.substring(0, 5) : '';
      const endTime = r.actual_end_time ? r.actual_end_time.substring(0, 5) : '';
      const approvedAt = r.approved_at ? new Date(r.approved_at).toLocaleString('ja-JP') : '';
      return `${r.id},${r.actual_date || ''},${startTime},${endTime},"${r.user_name}","${r.user_email}","${r.guide_name}","${r.guide_email}",${r.request_type},${r.request_date || ''},${approvedAt}`;
    }).join('\n');

    const csv = csvHeader + csvRows;

    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename=reports.csv');
    res.send('\ufeff' + csv); // BOMを追加してExcelで正しく表示
  } catch (error) {
    console.error('CSV出力エラー:', error);
    res.status(500).json({ error: 'CSV出力中にエラーが発生しました' });
  }
});

// CSV出力（利用実績）
router.get('/usage/csv', async (req, res) => {
  try {
    const { start_date, end_date } = req.query;

    let query = `SELECT r.id, r.actual_date, r.actual_start_time, r.actual_end_time,
                        TIMESTAMPDIFF(MINUTE, CONCAT(r.actual_date, ' ', r.actual_start_time), 
                                     CONCAT(r.actual_date, ' ', r.actual_end_time)) as duration_minutes,
                        u.name as user_name, g.name as guide_name,
                        req.request_type
                 FROM reports r
                 INNER JOIN users u ON r.user_id = u.id
                 INNER JOIN users g ON r.guide_id = g.id
                 INNER JOIN requests req ON r.request_id = req.id
                 WHERE r.status = 'approved'`;

    const params = [];

    if (start_date) {
      query += ' AND r.actual_date >= ?';
      params.push(start_date);
    }

    if (end_date) {
      query += ' AND r.actual_date <= ?';
      params.push(end_date);
    }

    query += ' ORDER BY r.actual_date DESC';

    const [reports] = await pool.execute(query, params);

    // CSV形式に変換
    const csvHeader = 'ID,利用日,開始時刻,終了時刻,利用時間(分),ユーザー名,ガイド名,依頼タイプ\n';
    const csvRows = reports.map(r => {
      const startTime = r.actual_start_time ? r.actual_start_time.substring(0, 5) : '';
      const endTime = r.actual_end_time ? r.actual_end_time.substring(0, 5) : '';
      return `${r.id},${r.actual_date || ''},${startTime},${endTime},${r.duration_minutes || 0},"${r.user_name}","${r.guide_name}",${r.request_type}`;
    }).join('\n');

    const csv = csvHeader + csvRows;

    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename=usage.csv');
    res.send('\ufeff' + csv);
  } catch (error) {
    console.error('利用実績CSV出力エラー:', error);
    res.status(500).json({ error: 'CSV出力中にエラーが発生しました' });
  }
});

// 統計情報取得
router.get('/stats', async (req, res) => {
  try {
    // ユーザー数
    const [userCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ?',
      ['user']
    );

    // ガイド数
    const [guideCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ?',
      ['guide']
    );

    // 承認済みユーザー数
    const [approvedUserCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_allowed = TRUE',
      ['user']
    );

    // 承認済みガイド数
    const [approvedGuideCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_allowed = TRUE',
      ['guide']
    );

    // 未承認ユーザー数
    const [pendingUserCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_allowed = FALSE',
      ['user']
    );

    // 未承認ガイド数
    const [pendingGuideCount] = await pool.execute(
      'SELECT COUNT(*) as count FROM users WHERE role = ? AND is_allowed = FALSE',
      ['guide']
    );

    // マッチング数（状態別）
    const [matchingStats] = await pool.execute(
      `SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
       FROM matchings`
    );

    // 依頼数（状態別）
    const [requestStats] = await pool.execute(
      `SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'guide_accepted' THEN 1 ELSE 0 END) as guide_accepted,
        SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
       FROM requests`
    );

    res.json({
      users: {
        total: userCount[0].count,
        approved: approvedUserCount[0].count,
        pending: pendingUserCount[0].count
      },
      guides: {
        total: guideCount[0].count,
        approved: approvedGuideCount[0].count,
        pending: pendingGuideCount[0].count
      },
      matchings: matchingStats[0],
      requests: requestStats[0]
    });
  } catch (error) {
    console.error('統計情報取得エラー:', error);
    res.status(500).json({ error: '統計情報の取得中にエラーが発生しました' });
  }
});

// ユーザー一覧取得
router.get('/users', async (req, res) => {
  try {
    const [users] = await pool.execute(
      `SELECT u.id, u.email, u.name, u.phone, u.role, u.is_allowed, u.created_at,
              up.contact_method, up.notes
       FROM users u
       LEFT JOIN user_profiles up ON u.id = up.user_id
       WHERE u.role = 'user'
       ORDER BY u.created_at DESC`
    );

    res.json({ users });
  } catch (error) {
    console.error('ユーザー一覧取得エラー:', error);
    res.status(500).json({ error: 'ユーザー一覧の取得中にエラーが発生しました' });
  }
});

// ガイド一覧取得
router.get('/guides', async (req, res) => {
  try {
    const [guides] = await pool.execute(
      `SELECT u.id, u.email, u.name, u.phone, u.role, u.is_allowed, u.created_at,
              gp.introduction, gp.available_areas, gp.available_days, gp.available_times
       FROM users u
       LEFT JOIN guide_profiles gp ON u.id = gp.user_id
       WHERE u.role = 'guide'
       ORDER BY u.created_at DESC`
    );

    // JSON文字列をパース
    const guidesWithParsedData = guides.map(guide => {
      if (guide.available_areas) {
        try {
          guide.available_areas = JSON.parse(guide.available_areas);
        } catch (e) {
          guide.available_areas = [];
        }
      }
      if (guide.available_days) {
        try {
          guide.available_days = JSON.parse(guide.available_days);
        } catch (e) {
          guide.available_days = [];
        }
      }
      if (guide.available_times) {
        try {
          guide.available_times = JSON.parse(guide.available_times);
        } catch (e) {
          guide.available_times = [];
        }
      }
      return guide;
    });

    res.json({ guides: guidesWithParsedData });
  } catch (error) {
    console.error('ガイド一覧取得エラー:', error);
    res.status(500).json({ error: 'ガイド一覧の取得中にエラーが発生しました' });
  }
});

// ユーザー承認
router.put('/users/:id/approve', async (req, res) => {
  try {
    const userId = req.params.id;

    // ユーザーの存在確認
    const [users] = await pool.execute(
      'SELECT id, role FROM users WHERE id = ? AND role = ?',
      [userId, 'user']
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'ユーザーが見つかりません' });
    }

    // 承認状態を更新
    await pool.execute(
      'UPDATE users SET is_allowed = TRUE WHERE id = ?',
      [userId]
    );

    // 通知を送信
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'approval', 'アカウントが承認されました', 'あなたのアカウントが承認されました。ログインできるようになりました。', ?)`,
      [userId, userId]
    );

    res.json({ message: 'ユーザーを承認しました' });
  } catch (error) {
    console.error('ユーザー承認エラー:', error);
    res.status(500).json({ error: 'ユーザー承認中にエラーが発生しました' });
  }
});

// ユーザー拒否
router.put('/users/:id/reject', async (req, res) => {
  try {
    const userId = req.params.id;

    // ユーザーの存在確認
    const [users] = await pool.execute(
      'SELECT id, role FROM users WHERE id = ? AND role = ?',
      [userId, 'user']
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'ユーザーが見つかりません' });
    }

    // 承認状態を更新
    await pool.execute(
      'UPDATE users SET is_allowed = FALSE WHERE id = ?',
      [userId]
    );

    // 通知を送信
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'approval', 'アカウントが拒否されました', '申し訳ございませんが、あなたのアカウントは承認されませんでした。', ?)`,
      [userId, userId]
    );

    res.json({ message: 'ユーザーを拒否しました' });
  } catch (error) {
    console.error('ユーザー拒否エラー:', error);
    res.status(500).json({ error: 'ユーザー拒否中にエラーが発生しました' });
  }
});

// ガイド承認
router.put('/guides/:id/approve', async (req, res) => {
  try {
    const guideId = req.params.id;

    // ガイドの存在確認
    const [guides] = await pool.execute(
      'SELECT id, role FROM users WHERE id = ? AND role = ?',
      [guideId, 'guide']
    );

    if (guides.length === 0) {
      return res.status(404).json({ error: 'ガイドが見つかりません' });
    }

    // 承認状態を更新
    await pool.execute(
      'UPDATE users SET is_allowed = TRUE WHERE id = ?',
      [guideId]
    );

    // 通知を送信
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'approval', 'アカウントが承認されました', 'あなたのアカウントが承認されました。ログインできるようになりました。', ?)`,
      [guideId, guideId]
    );

    res.json({ message: 'ガイドを承認しました' });
  } catch (error) {
    console.error('ガイド承認エラー:', error);
    res.status(500).json({ error: 'ガイド承認中にエラーが発生しました' });
  }
});

// ガイド拒否
router.put('/guides/:id/reject', async (req, res) => {
  try {
    const guideId = req.params.id;

    // ガイドの存在確認
    const [guides] = await pool.execute(
      'SELECT id, role FROM users WHERE id = ? AND role = ?',
      [guideId, 'guide']
    );

    if (guides.length === 0) {
      return res.status(404).json({ error: 'ガイドが見つかりません' });
    }

    // 承認状態を更新
    await pool.execute(
      'UPDATE users SET is_allowed = FALSE WHERE id = ?',
      [guideId]
    );

    // 通知を送信
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'approval', 'アカウントが拒否されました', '申し訳ございませんが、あなたのアカウントは承認されませんでした。', ?)`,
      [guideId, guideId]
    );

    res.json({ message: 'ガイドを拒否しました' });
  } catch (error) {
    console.error('ガイド拒否エラー:', error);
    res.status(500).json({ error: 'ガイド拒否中にエラーが発生しました' });
  }
});

module.exports = router;

