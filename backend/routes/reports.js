// 報告書関連のルート
const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../database');
const { authenticateToken, requireRole } = require('../middleware/auth');
const { sendNotification } = require('../utils/notifications');

const router = express.Router();

// 報告書作成・更新（ガイド）
router.post('/', authenticateToken, requireRole('guide'), [
  body('matching_id').isInt().withMessage('マッチングIDを指定してください'),
  body('service_content').optional(),
  body('report_content').optional(),
  body('actual_date').optional().isISO8601(),
  body('actual_start_time').optional().matches(/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/),
  body('actual_end_time').optional().matches(/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/)
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const {
      matching_id,
      service_content,
      report_content,
      actual_date,
      actual_start_time,
      actual_end_time
    } = req.body;

    const guideId = req.user.id;

    // マッチングの存在確認と権限チェック
    const [matchings] = await pool.execute(
      'SELECT id, user_id, request_id, guide_id FROM matchings WHERE id = ? AND guide_id = ?',
      [matching_id, guideId]
    );

    if (matchings.length === 0) {
      return res.status(404).json({ error: 'マッチングが見つかりません' });
    }

    const matching = matchings[0];

    // 既存の報告書を確認
    const [existingReports] = await pool.execute(
      'SELECT id, status FROM reports WHERE matching_id = ?',
      [matching_id]
    );

    if (existingReports.length > 0) {
      const report = existingReports[0];
      if (report.status === 'approved') {
        return res.status(400).json({ error: '既に承認済みの報告書です' });
      }

      // 既存の報告書を更新
      const updateFields = [];
      const updateValues = [];

      if (service_content !== undefined) {
        updateFields.push('service_content = ?');
        updateValues.push(service_content);
      }
      if (report_content !== undefined) {
        updateFields.push('report_content = ?');
        updateValues.push(report_content);
      }
      if (actual_date !== undefined) {
        updateFields.push('actual_date = ?');
        updateValues.push(actual_date);
      }
      if (actual_start_time !== undefined) {
        updateFields.push('actual_start_time = ?');
        updateValues.push(actual_start_time);
      }
      if (actual_end_time !== undefined) {
        updateFields.push('actual_end_time = ?');
        updateValues.push(actual_end_time);
      }

      if (updateFields.length > 0) {
        updateValues.push(matching_id);
        await pool.execute(
          `UPDATE reports SET ${updateFields.join(', ')}, status = 'draft' WHERE matching_id = ?`,
          updateValues
        );
      }

      return res.json({ message: '報告書が更新されました' });
    }

    // 依頼情報を取得（初期値として使用）
    const [requests] = await pool.execute(
      'SELECT service_content FROM requests WHERE id = ?',
      [matching.request_id]
    );

    const initialServiceContent = requests.length > 0 ? requests[0].service_content : '';

    // 新しい報告書を作成
    const [result] = await pool.execute(
      `INSERT INTO reports 
      (matching_id, request_id, guide_id, user_id, service_content, report_content, 
       actual_date, actual_start_time, actual_end_time, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')`,
      [matching_id, matching.request_id, guideId, matching.user_id,
       service_content || initialServiceContent, report_content || null,
       actual_date || null, actual_start_time || null, actual_end_time || null]
    );

    res.status(201).json({
      message: '報告書が作成されました',
      report_id: result.insertId
    });
  } catch (error) {
    console.error('報告書作成エラー:', error);
    res.status(500).json({ error: '報告書作成中にエラーが発生しました' });
  }
});

// 報告書提出（ガイド）
router.post('/:id/submit', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const reportId = req.params.id;
    const guideId = req.user.id;

    // 報告書の存在確認と権限チェック
    const [reports] = await pool.execute(
      'SELECT id, user_id, status FROM reports WHERE id = ? AND guide_id = ?',
      [reportId, guideId]
    );

    if (reports.length === 0) {
      return res.status(404).json({ error: '報告書が見つかりません' });
    }

    const report = reports[0];

    if (report.status === 'approved') {
      return res.status(400).json({ error: '既に承認済みの報告書です' });
    }

    // 報告書を提出状態に更新
    await pool.execute(
      `UPDATE reports SET status = 'submitted', submitted_at = NOW() WHERE id = ?`,
      [reportId]
    );

    // ユーザーに通知
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'report', '報告書が提出されました', 'ガイドから報告書が提出されました。承認または修正依頼を行ってください。', ?)`,
      [report.user_id, reportId]
    );

    res.json({ message: '報告書が提出されました' });
  } catch (error) {
    console.error('報告書提出エラー:', error);
    res.status(500).json({ error: '報告書提出中にエラーが発生しました' });
  }
});

// 報告書一覧取得（ガイド）
router.get('/my-reports', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const [reports] = await pool.execute(
      `SELECT r.*, u.name as user_name, req.request_type, req.request_date
       FROM reports r
       INNER JOIN users u ON r.user_id = u.id
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.guide_id = ?
       ORDER BY r.created_at DESC`,
      [req.user.id]
    );

    res.json({ reports });
  } catch (error) {
    console.error('報告書一覧取得エラー:', error);
    res.status(500).json({ error: '報告書一覧の取得中にエラーが発生しました' });
  }
});

// 利用時間統計取得（ユーザー）
router.get('/usage-stats', authenticateToken, requireRole('user'), async (req, res) => {
  try {
    const userId = req.user.id;
    const { year, month } = req.query;
    
    // 今月のデフォルト値
    const now = new Date();
    const targetYear = year || now.getFullYear();
    const targetMonth = month || (now.getMonth() + 1);

    // 月ごとの利用時間（過去12ヶ月）
    const [monthlyStats] = await pool.execute(
      `SELECT 
        DATE_FORMAT(r.actual_date, '%Y-%m') as month,
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       WHERE r.user_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND DATE_FORMAT(r.actual_date, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
       GROUP BY DATE_FORMAT(r.actual_date, '%Y-%m')
       ORDER BY month DESC
       LIMIT 12`,
      [userId]
    );

    // 今月の外出/自宅利用時間
    const [currentMonthStats] = await pool.execute(
      `SELECT 
        req.request_type,
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.user_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND YEAR(r.actual_date) = ?
         AND MONTH(r.actual_date) = ?
       GROUP BY req.request_type`,
      [userId, targetYear, targetMonth]
    );

    // 今月の総利用時間
    const [currentMonthTotal] = await pool.execute(
      `SELECT 
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       WHERE r.user_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND YEAR(r.actual_date) = ?
         AND MONTH(r.actual_date) = ?`,
      [userId, targetYear, targetMonth]
    );

    // データを整形
    const monthlyData = monthlyStats.map(stat => ({
      month: stat.month,
      total_minutes: stat.total_minutes || 0,
      total_hours: Math.round((stat.total_minutes || 0) / 60 * 10) / 10
    }));

    const typeStats = {
      '外出': 0,
      '自宅': 0
    };
    currentMonthStats.forEach(stat => {
      typeStats[stat.request_type] = Math.round(stat.total_minutes / 60 * 10) / 10;
    });

    res.json({
      monthly: monthlyData,
      current_month: {
        total_minutes: currentMonthTotal[0]?.total_minutes || 0,
        total_hours: Math.round((currentMonthTotal[0]?.total_minutes || 0) / 60 * 10) / 10,
        by_type: typeStats
      }
    });
  } catch (error) {
    console.error('利用時間統計取得エラー:', error);
    res.status(500).json({ error: '利用時間統計の取得中にエラーが発生しました' });
  }
});

// ガイド時間統計取得（ガイド）
router.get('/guide-stats', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const guideId = req.user.id;
    const { year, month } = req.query;
    
    // 今月のデフォルト値
    const now = new Date();
    const targetYear = year || now.getFullYear();
    const targetMonth = month || (now.getMonth() + 1);

    // 月ごとのガイド時間（過去12ヶ月）
    const [monthlyStats] = await pool.execute(
      `SELECT 
        DATE_FORMAT(r.actual_date, '%Y-%m') as month,
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       WHERE r.guide_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND DATE_FORMAT(r.actual_date, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
       GROUP BY DATE_FORMAT(r.actual_date, '%Y-%m')
       ORDER BY month DESC
       LIMIT 12`,
      [guideId]
    );

    // 今月の外出/自宅ガイド時間
    const [currentMonthStats] = await pool.execute(
      `SELECT 
        req.request_type,
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.guide_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND YEAR(r.actual_date) = ?
         AND MONTH(r.actual_date) = ?
       GROUP BY req.request_type`,
      [guideId, targetYear, targetMonth]
    );

    // 今月の総ガイド時間
    const [currentMonthTotal] = await pool.execute(
      `SELECT 
        SUM(TIMESTAMPDIFF(MINUTE, 
          CONCAT(r.actual_date, ' ', r.actual_start_time), 
          CONCAT(r.actual_date, ' ', r.actual_end_time)
        )) as total_minutes
       FROM reports r
       WHERE r.guide_id = ? 
         AND r.status = 'approved'
         AND r.actual_date IS NOT NULL
         AND r.actual_start_time IS NOT NULL
         AND r.actual_end_time IS NOT NULL
         AND YEAR(r.actual_date) = ?
         AND MONTH(r.actual_date) = ?`,
      [guideId, targetYear, targetMonth]
    );

    // データを整形
    const monthlyData = monthlyStats.map(stat => ({
      month: stat.month,
      total_minutes: stat.total_minutes || 0,
      total_hours: Math.round((stat.total_minutes || 0) / 60 * 10) / 10
    }));

    const typeStats = {
      '外出': 0,
      '自宅': 0
    };
    currentMonthStats.forEach(stat => {
      typeStats[stat.request_type] = Math.round(stat.total_minutes / 60 * 10) / 10;
    });

    res.json({
      monthly: monthlyData,
      current_month: {
        total_minutes: currentMonthTotal[0]?.total_minutes || 0,
        total_hours: Math.round((currentMonthTotal[0]?.total_minutes || 0) / 60 * 10) / 10,
        by_type: typeStats
      }
    });
  } catch (error) {
    console.error('ガイド時間統計取得エラー:', error);
    res.status(500).json({ error: 'ガイド時間統計の取得中にエラーが発生しました' });
  }
});

// 報告書詳細取得（ガイド）
router.get('/:id', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const reportId = req.params.id;
    const guideId = req.user.id;

    const [reports] = await pool.execute(
      `SELECT r.*, u.name as user_name, req.request_type, req.request_date
       FROM reports r
       INNER JOIN users u ON r.user_id = u.id
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.id = ? AND r.guide_id = ?`,
      [reportId, guideId]
    );

    if (reports.length === 0) {
      return res.status(404).json({ error: '報告書が見つかりません' });
    }

    res.json({ report: reports[0] });
  } catch (error) {
    console.error('報告書詳細取得エラー:', error);
    res.status(500).json({ error: '報告書詳細の取得中にエラーが発生しました' });
  }
});

// 報告書承認待ち一覧取得（ユーザー）
router.get('/user/pending', authenticateToken, requireRole('user'), async (req, res) => {
  try {
    const [reports] = await pool.execute(
      `SELECT r.*, u.name as guide_name, req.request_type, req.request_date
       FROM reports r
       INNER JOIN users u ON r.guide_id = u.id
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.user_id = ? AND r.status = 'submitted'
       ORDER BY r.submitted_at DESC`,
      [req.user.id]
    );

    res.json({ reports });
  } catch (error) {
    console.error('承認待ち報告書一覧取得エラー:', error);
    res.status(500).json({ error: '報告書一覧の取得中にエラーが発生しました' });
  }
});

// 報告書詳細取得（ユーザー）
router.get('/user/:id', authenticateToken, requireRole('user'), async (req, res) => {
  try {
    const reportId = req.params.id;
    const userId = req.user.id;

    const [reports] = await pool.execute(
      `SELECT r.*, u.name as guide_name, req.request_type, req.request_date
       FROM reports r
       INNER JOIN users u ON r.guide_id = u.id
       INNER JOIN requests req ON r.request_id = req.id
       WHERE r.id = ? AND r.user_id = ?`,
      [reportId, userId]
    );

    if (reports.length === 0) {
      return res.status(404).json({ error: '報告書が見つかりません' });
    }

    res.json({ report: reports[0] });
  } catch (error) {
    console.error('報告書詳細取得エラー:', error);
    res.status(500).json({ error: '報告書詳細の取得中にエラーが発生しました' });
  }
});

// 報告書承認（ユーザー）
router.post('/:id/approve', authenticateToken, requireRole('user'), async (req, res) => {
  try {
    const reportId = req.params.id;
    const userId = req.user.id;

    // 報告書の存在確認と権限チェック
    const [reports] = await pool.execute(
      'SELECT id, guide_id, matching_id FROM reports WHERE id = ? AND user_id = ? AND status = ?',
      [reportId, userId, 'submitted']
    );

    if (reports.length === 0) {
      return res.status(404).json({ error: '承認待ちの報告書が見つかりません' });
    }

    const report = reports[0];

    // 報告書を承認状態に更新
    await pool.execute(
      `UPDATE reports SET status = 'approved', approved_at = NOW() WHERE id = ?`,
      [reportId]
    );

    // マッチングを完了状態に更新
    await pool.execute(
      `UPDATE matchings SET status = 'completed', completed_at = NOW() WHERE id = ?`,
      [report.matching_id]
    );

    // ガイドと管理者に通知
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'report', '報告書が承認されました', '報告書が承認されました。', ?)`,
      [report.guide_id, reportId]
    );

    const [admins] = await pool.execute('SELECT id FROM users WHERE role = ?', ['admin']);
    for (const admin of admins) {
      await pool.execute(
        `INSERT INTO notifications (user_id, type, title, message, related_id)
         VALUES (?, 'report', '報告書が承認されました', '報告書が承認されました。', ?)`,
        [admin.id, reportId]
      );
    }

    res.json({ message: '報告書を承認しました' });
  } catch (error) {
    console.error('報告書承認エラー:', error);
    res.status(500).json({ error: '報告書承認中にエラーが発生しました' });
  }
});

// 報告書修正依頼（ユーザー）
router.post('/:id/request-revision', authenticateToken, requireRole('user'), [
  body('revision_notes').notEmpty().withMessage('修正内容を入力してください')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const reportId = req.params.id;
    const userId = req.user.id;
    const { revision_notes } = req.body;

    // 報告書の存在確認と権限チェック
    const [reports] = await pool.execute(
      'SELECT id, guide_id FROM reports WHERE id = ? AND user_id = ? AND status = ?',
      [reportId, userId, 'submitted']
    );

    if (reports.length === 0) {
      return res.status(404).json({ error: '承認待ちの報告書が見つかりません' });
    }

    const report = reports[0];

    // 報告書を修正依頼状態に更新
    await pool.execute(
      `UPDATE reports SET status = 'revision_requested' WHERE id = ?`,
      [reportId]
    );

    // ガイドに通知
    await pool.execute(
      `INSERT INTO notifications (user_id, type, title, message, related_id)
       VALUES (?, 'report', '報告書の修正依頼', ?, ?)`,
      [report.guide_id, `修正依頼: ${revision_notes}`, reportId]
    );

    res.json({ message: '修正依頼を送信しました' });
  } catch (error) {
    console.error('修正依頼エラー:', error);
    res.status(500).json({ error: '修正依頼中にエラーが発生しました' });
  }
});

module.exports = router;

