// ユーザー関連のルート
const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../database');
const { authenticateToken, requireRole } = require('../middleware/auth');

const router = express.Router();

// 統計情報取得（管理者のみ）
// 注意: ルートの順序が重要。'/profile'より前に定義する必要がある
router.get('/stats', authenticateToken, requireRole('admin'), async (req, res) => {
  try {
    console.log('GET /api/users/stats - リクエスト受信');
    console.log('User:', req.user);
    // ユーザー数（ロール別）
    console.log('=========================================================');
    const [userStats] = await pool.execute(
      `SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_allowed = TRUE THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_allowed = FALSE THEN 1 ELSE 0 END) as pending
       FROM users WHERE role = ?`,
      ['user']
    );

    // ガイド数（ロール別）
    const [guideStats] = await pool.execute(
      `SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_allowed = TRUE THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_allowed = FALSE THEN 1 ELSE 0 END) as pending
       FROM users WHERE role = ?`,
      ['guide']
    );

    res.json({
      users: {
        total: userStats[0].total || 0,
        approved: userStats[0].approved || 0,
        pending: userStats[0].pending || 0
      },
      guides: {
        total: guideStats[0].total || 0,
        approved: guideStats[0].approved || 0,
        pending: guideStats[0].pending || 0
      }
    });
  } catch (error) {
    console.error('統計情報取得エラー:', error);
    res.status(500).json({ error: '統計情報の取得中にエラーが発生しました' });
  }
});

// プロフィール取得
router.get('/profile', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;
    const userRole = req.user.role;

    // ユーザー基本情報取得
    const [users] = await pool.execute(
      'SELECT id, email, name, phone, role, created_at FROM users WHERE id = ?',
      [userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'ユーザーが見つかりません' });
    }

    const user = users[0];

    // プロフィール情報も取得
    if (userRole === 'user') {
      const [profiles] = await pool.execute(
        'SELECT contact_method, notes FROM user_profiles WHERE user_id = ?',
        [user.id]
      );
      user.profile = profiles[0] || {};
    } else if (userRole === 'guide') {
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
    console.error('プロフィール取得エラー:', error);
    res.status(500).json({ error: 'プロフィールの取得中にエラーが発生しました' });
  }
});

// プロフィール更新（ユーザー）
router.put('/profile', authenticateToken, [
  body('name').optional().notEmpty().withMessage('名前を入力してください'),
  body('phone').optional().custom((value) => {
    if (value === '' || value === null || value === undefined) {
      return true; // 空文字列、null、undefinedは許可
    }
    // 電話番号の形式チェック（より柔軟な形式を許可）
    const phoneRegex = /^[\d\-\+\(\)\s]+$/;
    if (!phoneRegex.test(value)) {
      throw new Error('有効な電話番号を入力してください');
    }
    return true;
  }),
  body('contact_method').optional()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      console.error('バリデーションエラー:', errors.array());
      return res.status(400).json({ 
        error: errors.array().map(e => e.msg).join(', '),
        errors: errors.array()
      });
    }

    const { name, phone, contact_method, notes } = req.body;
    const userId = req.user.id;

    // ユーザー情報更新
    if (name || phone !== undefined) {
      const updateFields = [];
      const updateValues = [];

      if (name) {
        updateFields.push('name = ?');
        updateValues.push(name);
      }
      if (phone !== undefined) {
        updateFields.push('phone = ?');
        updateValues.push(phone);
      }

      if (updateFields.length > 0) {
        updateValues.push(userId);
        await pool.execute(
          `UPDATE users SET ${updateFields.join(', ')} WHERE id = ?`,
          updateValues
        );
      }
    }

    // プロフィール更新
    if (req.user.role === 'user') {
      const [existingProfiles] = await pool.execute(
        'SELECT id FROM user_profiles WHERE user_id = ?',
        [userId]
      );

      if (existingProfiles.length > 0) {
        const profileFields = [];
        const profileValues = [];

        if (contact_method !== undefined) {
          profileFields.push('contact_method = ?');
          profileValues.push(contact_method);
        }
        if (notes !== undefined) {
          profileFields.push('notes = ?');
          profileValues.push(notes);
        }

        if (profileFields.length > 0) {
          profileValues.push(userId);
          await pool.execute(
            `UPDATE user_profiles SET ${profileFields.join(', ')} WHERE user_id = ?`,
            profileValues
          );
        }
      } else {
        await pool.execute(
          'INSERT INTO user_profiles (user_id, contact_method, notes) VALUES (?, ?, ?)',
          [userId, contact_method || null, notes || null]
        );
      }
    }

    res.json({ message: 'プロフィールが更新されました' });
  } catch (error) {
    console.error('プロフィール更新エラー:', error);
    res.status(500).json({ error: 'プロフィール更新中にエラーが発生しました' });
  }
});

// ガイドプロフィール更新
router.put('/guide-profile', authenticateToken, [
  body('introduction').optional(),
  body('available_areas').optional().isArray().withMessage('対応可能エリアは配列形式で入力してください'),
  body('available_days').optional().isArray().withMessage('対応可能日は配列形式で入力してください'),
  body('available_times').optional().isArray().withMessage('対応可能時間は配列形式で入力してください')
], async (req, res) => {
  try {
    if (req.user.role !== 'guide') {
      return res.status(403).json({ error: 'ガイドのみがこの操作を実行できます' });
    }

    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { introduction, available_areas, available_days, available_times } = req.body;
    const userId = req.user.id;

    const [existingProfiles] = await pool.execute(
      'SELECT id FROM guide_profiles WHERE user_id = ?',
      [userId]
    );

    const updateFields = [];
    const updateValues = [];

    if (introduction !== undefined) {
      updateFields.push('introduction = ?');
      updateValues.push(introduction);
    }
    if (available_areas !== undefined) {
      updateFields.push('available_areas = ?');
      updateValues.push(JSON.stringify(available_areas));
    }
    if (available_days !== undefined) {
      updateFields.push('available_days = ?');
      updateValues.push(JSON.stringify(available_days));
    }
    if (available_times !== undefined) {
      updateFields.push('available_times = ?');
      updateValues.push(JSON.stringify(available_times));
    }

    if (updateFields.length > 0) {
      if (existingProfiles.length > 0) {
        updateValues.push(userId);
        await pool.execute(
          `UPDATE guide_profiles SET ${updateFields.join(', ')} WHERE user_id = ?`,
          updateValues
        );
      } else {
        await pool.execute(
          `INSERT INTO guide_profiles (user_id, ${updateFields.map(f => f.split(' = ')[0]).join(', ')}) VALUES (?, ${updateFields.map(() => '?').join(', ')})`,
          [userId, ...updateValues]
        );
      }
    }

    res.json({ message: 'ガイドプロフィールが更新されました' });
  } catch (error) {
    console.error('ガイドプロフィール更新エラー:', error);
    res.status(500).json({ error: 'ガイドプロフィール更新中にエラーが発生しました' });
  }
});

module.exports = router;

