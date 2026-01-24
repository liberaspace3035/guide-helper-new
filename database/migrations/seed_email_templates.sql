-- メールテンプレートの初期データ（PostgreSQL版）
-- 文字化けを防ぐため、UTF-8で保存されていることを確認

-- 既存のテンプレートを削除（再初期化用）
DELETE FROM email_templates;

-- メールテンプレートの初期データを挿入
INSERT INTO email_templates (template_key, subject, body, is_active) VALUES
('request_notification', 
 '新しい依頼が登録されました',
 '新しい依頼が登録されました。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\n詳細を確認して、承諾してください。',
 TRUE),

('matching_notification',
 'マッチングが成立しました',
 'マッチングが成立しました。チャットで詳細を確認してください。\n\nマッチングID: {{matching_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}',
 TRUE),

('report_submitted',
 '報告書が提出されました',
 'ガイドから報告書が提出されました。承認または修正依頼を行ってください。\n\n報告書ID: {{report_id}}\nガイド名: {{guide_name}}\n実施日: {{actual_date}}',
 TRUE),

('report_approved',
 '報告書が承認されました',
 '報告書が承認されました。\n\n報告書ID: {{report_id}}\n実施日: {{actual_date}}',
 TRUE),

('reminder_pending_request',
 '承認待ちの依頼があります',
 '承認待ちの依頼があります。確認をお願いします。\n\n依頼ID: {{request_id}}',
 TRUE);

-- メール通知設定の初期データ
DELETE FROM email_notification_settings;

INSERT INTO email_notification_settings (notification_type, is_enabled, reminder_days) VALUES
('request', TRUE, NULL),
('matching', TRUE, NULL),
('report', TRUE, NULL),
('reminder', TRUE, 3);
