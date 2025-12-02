// 管理者アカウントを設定するスクリプト
// 使用方法: node scripts/setup-admin.js [email] [password]
const pool = require('../database');
const bcrypt = require('bcryptjs');

async function setupAdmin() {
  try {
    // コマンドライン引数からメールアドレスとパスワードを取得
    const email = process.argv[2] || 'admin@example.com';
    const password = process.argv[3] || 'admin123';
    const name = process.argv[4] || '管理者';

    console.log('管理者アカウントを設定します...');
    console.log(`Email: ${email}`);
    console.log(`Name: ${name}\n`);

    // 既存のユーザーをチェック
    const [existingUsers] = await pool.execute(
      'SELECT id, email, role, is_allowed FROM users WHERE email = ?',
      [email]
    );

    if (existingUsers.length > 0) {
      const existingUser = existingUsers[0];
      console.log(`既存のアカウントが見つかりました: ${existingUser.role}`);
      
      // 管理者ロールに変更し、承認
      await pool.execute(
        'UPDATE users SET role = ?, is_allowed = TRUE WHERE email = ?',
        ['admin', email]
      );
      
      // パスワードが指定されている場合は更新
      if (process.argv[3]) {
        const passwordHash = await bcrypt.hash(password, 10);
        await pool.execute(
          'UPDATE users SET password_hash = ? WHERE email = ?',
          [passwordHash, email]
        );
        console.log('パスワードを更新しました。');
      }
      
      console.log('管理者ロールに変更し、承認しました。');
    } else {
      // 新しい管理者アカウントを作成
      const passwordHash = await bcrypt.hash(password, 10);
      const [result] = await pool.execute(
        'INSERT INTO users (email, password_hash, name, role, is_allowed) VALUES (?, ?, ?, ?, ?)',
        [email, passwordHash, name, 'admin', true]
      );
      console.log(`管理者アカウントを作成しました。ID: ${result.insertId}`);
    }

    // 最終確認
    const [admins] = await pool.execute(
      'SELECT id, email, name, role, is_allowed FROM users WHERE role = ?',
      ['admin']
    );
    console.log('\n管理者アカウント一覧:');
    admins.forEach(admin => {
      console.log(`  ID: ${admin.id}, Email: ${admin.email}, Name: ${admin.name}, 承認状態: ${admin.is_allowed ? '承認済み' : '未承認'}`);
    });

    console.log('\n管理者としてログインできます！');
    console.log(`メールアドレス: ${email}`);
    console.log(`パスワード: ${password}`);

  } catch (error) {
    console.error('エラー:', error);
  } finally {
    pool.end();
  }
}

setupAdmin();

