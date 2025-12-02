// 通知送信ユーティリティ
const nodemailer = require('nodemailer');

// メール送信設定（環境変数から取得）
let transporter = null;

if (process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASS) {
  transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: parseInt(process.env.SMTP_PORT) || 587,
    secure: false,
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS
    }
  });
}

/**
 * メール通知を送信
 * @param {string} to - 送信先メールアドレス
 * @param {string} subject - 件名
 * @param {string} text - 本文
 */
async function sendEmailNotification(to, subject, text) {
  if (!transporter) {
    console.log('メール設定がありません。メール通知をスキップします。');
    return;
  }

  try {
    await transporter.sendMail({
      from: process.env.SMTP_USER,
      to,
      subject,
      text
    });
    console.log(`メール通知を送信しました: ${to}`);
  } catch (error) {
    console.error('メール送信エラー:', error);
  }
}

/**
 * LINE Webhook通知（簡易版）
 * @param {string} webhookUrl - Webhook URL
 * @param {object} data - 送信データ
 */
async function sendLineWebhook(webhookUrl, data) {
  // 実装は後で追加（axios等を使用）
  console.log('LINE Webhook通知:', webhookUrl, data);
}

/**
 * 通知を送信（統合関数）
 * @param {string} email - メールアドレス
 * @param {string} type - 通知タイプ
 * @param {object} data - 通知データ
 */
async function sendNotification(email, type, data) {
  const notifications = {
    request: {
      subject: '新しい依頼が届きました',
      text: `新しい依頼が届きました。詳細を確認してください。\n\n${JSON.stringify(data, null, 2)}`
    },
    acceptance: {
      subject: 'ガイドが依頼を承諾しました',
      text: `ガイドが依頼を承諾しました。マッチングを確認してください。\n\n${JSON.stringify(data, null, 2)}`
    },
    matching: {
      subject: 'マッチングが成立しました',
      text: `マッチングが成立しました。チャットで詳細を確認してください。\n\n${JSON.stringify(data, null, 2)}`
    },
    report: {
      subject: '報告書が提出されました',
      text: `報告書が提出されました。承認または修正依頼を行ってください。\n\n${JSON.stringify(data, null, 2)}`
    }
  };

  const notification = notifications[type];
  if (notification && email) {
    await sendEmailNotification(email, notification.subject, notification.text);
  }
}

module.exports = {
  sendEmailNotification,
  sendLineWebhook,
  sendNotification
};

