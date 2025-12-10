// 依頼関連のルート
const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../database');
const { authenticateToken, requireRole } = require('../middleware/auth');
const { maskAddress } = require('../utils/maskAddress');
const { formatText, formatVoiceText } = require('../utils/textFormatter');
const { sendNotification } = require('../utils/notifications');

const router = express.Router();

// 依頼作成
router.post('/', authenticateToken, requireRole('user'), [
  body('request_type').isIn(['外出', '自宅']).withMessage('依頼タイプを選択してください'),
  body('destination_address').notEmpty().withMessage('住所を入力してください'),
  body('service_content').notEmpty().withMessage('サービス内容を入力してください'),
  body('request_date').isISO8601().withMessage('有効な日付を入力してください'),
  body('start_time').matches(/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/).withMessage('有効な開始時刻を入力してください'),
  body('end_time').matches(/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/).withMessage('有効な終了時刻を入力してください'),
  body('meeting_place').optional()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const {
      request_type,
      destination_address,
      meeting_place,
      service_content,
      request_date,
      request_time, // 後方互換性のため
      start_time,
      end_time,
      duration,
      notes,
      is_voice_input
    } = req.body;

    // 後方互換性: request_timeが指定されている場合はstart_timeとして使用
    const finalStartTime = start_time || request_time;
    const finalEndTime = end_time;

    // バリデーション: 終了時刻が開始時刻より後であることを確認
    if (finalStartTime && finalEndTime && finalStartTime >= finalEndTime) {
      return res.status(400).json({ error: '終了時刻は開始時刻より後である必要があります' });
    }

    // 外出依頼の場合、待ち合わせ場所が必須
    if (request_type === '外出' && !meeting_place) {
      return res.status(400).json({ error: '待ち合わせ場所を入力してください' });
    }

    const userId = req.user.id;

    // 承認待ちの報告書がある場合は新規依頼を作成できない
    const [pendingReports] = await pool.execute(
      `SELECT id FROM reports WHERE user_id = ? AND status = 'submitted'`,
      [userId]
    );

    if (pendingReports.length > 0) {
      return res.status(403).json({ 
        error: '承認待ちの報告書があります。承認または修正依頼を完了してから新しい依頼を作成してください' 
      });
    }

    // テキスト整形
    let formatted_notes = notes;
    if (notes) {
      formatted_notes = is_voice_input ? formatVoiceText(notes) : formatText(notes);
    }

    // 住所マスキング
    const masked_address = maskAddress(destination_address);

    // 依頼作成
    const [result] = await pool.execute(
      `INSERT INTO requests 
      (user_id, request_type, destination_address, meeting_place, masked_address, service_content, request_date, request_time, start_time, end_time, duration, notes, formatted_notes, status) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')`,
      [
        userId, 
        request_type, 
        destination_address, 
        meeting_place || null,
        masked_address, 
        service_content,
        request_date, 
        finalStartTime, // 後方互換性のためrequest_timeにも保存
        finalStartTime,
        finalEndTime || null,
        duration || null, 
        notes || null, 
        formatted_notes || null
      ]
    );

    const requestId = result.insertId;

    // 条件に合致するガイドを検索して通知
    await notifyMatchingGuides(requestId, request_date, finalStartTime, masked_address);

    res.status(201).json({
      message: '依頼が作成されました',
      request: {
        id: requestId,
        status: 'pending'
      }
    });
  } catch (error) {
    console.error('依頼作成エラー:', error);
    res.status(500).json({ error: '依頼作成中にエラーが発生しました' });
  }
});

// 条件に合致するガイドに通知を送信
async function notifyMatchingGuides(requestId, requestDate, requestTime, maskedAddress) {
  try {
    // リクエストの日付から曜日を取得（簡易版）
    const date = new Date(requestDate);
    const dayOfWeek = date.getDay(); // 0=日, 1=月, ..., 6=土
    const dayType = (dayOfWeek === 0 || dayOfWeek === 6) ? '土日' : '平日';

    // 時刻から時間帯を判定
    const hour = parseInt(requestTime.split(':')[0]);
    let timeType = '午後';
    if (hour < 12) {
      timeType = '午前';
    } else if (hour >= 18) {
      timeType = '夜間';
    }

    // 条件に合致するガイドを検索
    const [guides] = await pool.execute(
      `SELECT u.id, u.email, u.name, gp.available_areas, gp.available_days, gp.available_times
       FROM users u
       INNER JOIN guide_profiles gp ON u.id = gp.user_id
       WHERE u.role = 'guide'`
    );

    for (const guide of guides) {
      let isMatching = false;

      // エリアチェック
      if (guide.available_areas) {
        const areas = JSON.parse(guide.available_areas);
        if (maskedAddress && areas.some(area => maskedAddress.includes(area))) {
          isMatching = true;
        }
      }

      // 日付チェック
      if (guide.available_days) {
        const days = JSON.parse(guide.available_days);
        if (days.includes(dayType)) {
          isMatching = true;
        }
      }

      // 時間チェック
      if (guide.available_times) {
        const times = JSON.parse(guide.available_times);
        if (times.includes(timeType)) {
          isMatching = true;
        }
      }

      // 条件に合致する場合は通知を送信
      if (isMatching) {
        // 通知レコード作成
        await pool.execute(
          `INSERT INTO notifications (user_id, type, title, message, related_id)
           VALUES (?, 'request', '新しい依頼が届きました', '条件に合致する依頼が届きました。詳細を確認してください。', ?)`,
          [guide.id, requestId]
        );

        // メール通知（実装は後で）
        // await sendNotification(guide.email, '新しい依頼', '...');
      }
    }
  } catch (error) {
    console.error('ガイド通知エラー:', error);
  }
}

// 依頼一覧取得（ユーザー）
router.get('/my-requests', authenticateToken, requireRole('user'), async (req, res) => {
  try {
    const [requests] = await pool.execute(
      `SELECT r.id, r.request_type, r.masked_address, r.service_content, r.request_date, 
              r.request_time, r.duration, r.status, r.created_at,
              m.id as matching_id
       FROM requests r
       LEFT JOIN matchings m ON r.id = m.request_id
       WHERE r.user_id = ? 
       ORDER BY r.created_at DESC`,
      [req.user.id]
    );

    res.json({ requests });
  } catch (error) {
    console.error('依頼一覧取得エラー:', error);
    res.status(500).json({ error: '依頼一覧の取得中にエラーが発生しました' });
  }
});

// 依頼詳細取得（ユーザー）
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const requestId = req.params.id;
    const userId = req.user.id;
    const userRole = req.user.role;

    let query, params;

    if (userRole === 'user') {
      // ユーザーは自分の依頼の詳細を取得（マスキングなし）
      query = `SELECT * FROM requests WHERE id = ? AND user_id = ?`;
      params = [requestId, userId];
    } else if (userRole === 'guide') {
      // ガイドはマスキングされた住所のみ表示
      query = `SELECT id, user_id, request_type, masked_address, service_content, 
                      request_date, request_time, duration, formatted_notes, status, created_at
               FROM requests WHERE id = ?`;
      params = [requestId];
    } else {
      // 管理者は全ての情報を取得
      query = `SELECT * FROM requests WHERE id = ?`;
      params = [requestId];
    }

    const [requests] = await pool.execute(query, params);

    if (requests.length === 0) {
      return res.status(404).json({ error: '依頼が見つかりません' });
    }

    res.json({ request: requests[0] });
  } catch (error) {
    console.error('依頼詳細取得エラー:', error);
    res.status(500).json({ error: '依頼詳細の取得中にエラーが発生しました' });
  }
});

// ガイド向け依頼一覧（通知なし、個人情報マスキング）
router.get('/guide/available', authenticateToken, requireRole('guide'), async (req, res) => {
  try {
    const [requests] = await pool.execute(
      `SELECT id, request_type, masked_address, service_content, request_date, 
              request_time, duration, status, created_at
       FROM requests 
       WHERE status = 'pending' OR status = 'guide_accepted'
       ORDER BY created_at DESC`
    );

    res.json({ requests });
  } catch (error) {
    console.error('ガイド向け依頼一覧取得エラー:', error);
    res.status(500).json({ error: '依頼一覧の取得中にエラーが発生しました' });
  }
});

module.exports = router;

