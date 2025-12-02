// 管理者アカウントを作成するスクリプト
const pool = require('../database');
const bcrypt = require('bcryptjs');
const readline = require('readline');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

function question(query) {
  return new Promise(resolve => rl.question(query, resolve));
}

async function createAdmin() {
  try {
    console.log('管理者アカウントを作成します。\n');

    const email = await question('メールアドレス: ');
    if (!email) {
      console.log('メールアドレスが入力されていません。');
      rl.close();
      pool.end();
      return;
    }

    // 既存のユーザーをチェック
    const [existingUsers] = await pool.execute(
      'SELECT id, email, role FROM users WHERE email = ?',
      [email]
    );

    if (existingUsers.length > 0) {
      const existingUser = existingUsers[0];
      if (existingUser.role === 'admin') {
        console.log(`\n${email} は既に管理者アカウントです。`);
        // 承認状態を確認
        const [users] = await pool.execute(
          'SELECT is_allowed FROM users WHERE email = ?',
          [email]
        );
        if (!users[0].is_allowed) {
          console.log('管理者アカウントが未承認です。承認します...');
          await pool.execute(
            'UPDATE users SET is_allowed = TRUE WHERE email = ?',
            [email]
          );
          console.log('管理者アカウントを承認しました。');
        } else {
          console.log('管理者アカウントは既に承認されています。');
        }
      } else {
        console.log(`\n${email} は既に${existingUser.role}として登録されています。`);
        const changeRole = await question('管理者ロールに変更しますか？ (y/n): ');
        if (changeRole.toLowerCase() === 'y') {
          await pool.execute(
            'UPDATE users SET role = ?, is_allowed = TRUE WHERE email = ?',
            ['admin', email]
          );
          console.log('管理者ロールに変更し、承認しました。');
        }
      }
      rl.close();
      pool.end();
      return;
    }

    const name = await question('名前: ');
    const password = await question('パスワード: ');

    if (!name || !password) {
      console.log('名前とパスワードは必須です。');
      rl.close();
      pool.end();
      return;
    }

    // パスワードのハッシュ化
    const passwordHash = await bcrypt.hash(password, 10);

    // ユーザー作成
    const [result] = await pool.execute(
      'INSERT INTO users (email, password_hash, name, role, is_allowed) VALUES (?, ?, ?, ?, ?)',
      [email, passwordHash, name, 'admin', true]
    );

    console.log(`\n管理者アカウントを作成しました。`);
    console.log(`ID: ${result.insertId}`);
    console.log(`Email: ${email}`);
    console.log(`Name: ${name}`);
    console.log(`Role: admin`);
    console.log(`承認状態: 承認済み`);

  } catch (error) {
    console.error('エラー:', error);
  } finally {
    rl.close();
    pool.end();
  }
}

createAdmin();

