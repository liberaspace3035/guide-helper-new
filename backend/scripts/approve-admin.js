// 管理者アカウントを承認するスクリプト
const pool = require('../database');

async function approveAdmin() {
  try {
    // 管理者アカウントを取得
    const [admins] = await pool.execute(
      'SELECT id, email, name, role, is_allowed FROM users WHERE role = ?',
      ['admin']
    );

    if (admins.length === 0) {
      console.log('管理者アカウントが見つかりません。');
      console.log('管理者アカウントを作成するには、以下のコマンドを実行してください:');
      console.log('node scripts/grant-admin.js');
      return;
    }

    console.log('管理者アカウント:');
    admins.forEach(admin => {
      console.log(`  ID: ${admin.id}, Email: ${admin.email}, Name: ${admin.name}, 承認状態: ${admin.is_allowed ? '承認済み' : '未承認'}`);
    });

    // 未承認の管理者を承認
    const unapprovedAdmins = admins.filter(admin => !admin.is_allowed);
    if (unapprovedAdmins.length > 0) {
      console.log('\n未承認の管理者アカウントを承認します...');
      await pool.execute(
        'UPDATE users SET is_allowed = TRUE WHERE role = ?',
        ['admin']
      );
      console.log('管理者アカウントを承認しました。');
    } else {
      console.log('\nすべての管理者アカウントは既に承認されています。');
    }

    // 最終確認
    const [updatedAdmins] = await pool.execute(
      'SELECT id, email, name, is_allowed FROM users WHERE role = ?',
      ['admin']
    );
    console.log('\n更新後の管理者アカウント:');
    updatedAdmins.forEach(admin => {
      console.log(`  Email: ${admin.email}, 承認状態: ${admin.is_allowed ? '承認済み' : '未承認'}`);
    });

  } catch (error) {
    console.error('エラー:', error);
  } finally {
    pool.end();
  }
}

approveAdmin();

