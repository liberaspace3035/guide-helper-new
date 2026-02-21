<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // メールテンプレートの初期データ
        $templates = [
            [
                'template_key' => 'request_notification',
                'subject' => '新しい依頼が登録されました',
                'body' => '新しい依頼が登録されました。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\n詳細を確認して、承諾してください。',
                'is_active' => true,
            ],
            [
                'template_key' => 'matching_notification',
                'subject' => 'マッチングが成立しました',
                'body' => 'マッチングが成立しました。チャットで詳細を確認してください。\n\nマッチングID: {{matching_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}',
                'is_active' => true,
            ],
            [
                'template_key' => 'report_submitted',
                'subject' => '報告書が提出されました',
                'body' => 'ガイドから報告書が提出されました。承認または修正依頼を行ってください。\n\n報告書ID: {{report_id}}\nガイド名: {{guide_name}}\n実施日: {{actual_date}}',
                'is_active' => true,
            ],
            [
                'template_key' => 'report_approved',
                'subject' => '報告書が承認されました',
                'body' => '報告書が承認されました。\n\n報告書ID: {{report_id}}\n実施日: {{actual_date}}',
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_pending_request',
                'subject' => '承認待ちの依頼があります',
                'body' => '承認待ちの依頼があります。確認をお願いします。\n\n依頼ID: {{request_id}}',
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_report_missing',
                'subject' => '報告書の提出のお願い',
                'body' => "{{user_name}} 様\n\n以下の依頼について、報告書が未提出です。\nお手数ですが、報告書の提出をお願いいたします。\n\nマッチングID: {{matching_id}}\n依頼日: {{request_date}}\n\nログインのうえ、報告書の作成・提出をお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_same_day',
                'subject' => '本日の依頼のご案内',
                'body' => "{{user_name}} 様\n\n本日は以下の依頼が予定されています。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\nご確認のうえ、よろしくお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_day_before',
                'subject' => '明日の依頼のご案内',
                'body' => "{{user_name}} 様\n\n明日は以下の依頼が予定されています。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\nご確認のうえ、よろしくお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'password_reset',
                'subject' => 'パスワードリセットのご案内',
                'body' => "{{user_name}} 様\n\nパスワードリセットのリクエストを受け付けました。\n\n以下のリンクをクリックして、新しいパスワードを設定してください。\nこのリンクは60分間有効です。\n\n{{reset_url}}\n\nこのリクエストをしていない場合は、このメールを無視してください。\n\nガイドヘルパーマッチングサービス",
                'is_active' => true,
            ],
            [
                'template_key' => 'report_revision_requested',
                'subject' => '報告書に修正依頼がありました',
                'body' => "{{user_name}} 様\n\n報告書に修正依頼がありました。\n\n報告書ID: {{report_id}}\n実施日: {{actual_date}}\n\n修正内容:\n{{revision_notes}}\n\nログインのうえ、報告書を修正のうえ再提出をお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'report_admin_revision_requested',
                'subject' => '報告書が管理者により差し戻されました',
                'body' => "{{user_name}} 様\n\n報告書が管理者により差し戻されました。\n\n報告書ID: {{report_id}}\n実施日: {{actual_date}}\n\n修正内容:\n{{revision_notes}}\n\nログインのうえ、報告書を修正のうえ再提出をお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'announcement_reminder_unread',
                'subject' => '未読のお知らせがあります',
                'body' => '{{user_name}} 様\n\n以下のお知らせが未読です。ご確認ください。\n\n{{unread_list}}\n\nログインのうえ、お知らせ一覧からご確認をお願いいたします。',
                'is_active' => true,
            ],
            [
                'template_key' => 'user_registration_thanks',
                'subject' => 'ユーザー登録のご案内',
                'body' => "{{user_name}} 様\n\nこの度は、ガイドヘルパーマッチングサービスにご登録いただき、誠にありがとうございます。\n\nご登録いただいた内容を確認させていただき、審査を実施いたします。\n審査完了後、改めてご連絡させていただきます。\n\nご不明な点がございましたら、お気軽にお問い合わせください。\n\n今後ともよろしくお願いいたします。",
                'is_active' => true,
            ],
            [
                'template_key' => 'guide_registration_thanks',
                'subject' => 'ガイド登録のご案内',
                'body' => "{{user_name}} 様\n\nこの度は、ガイドヘルパーマッチングサービスにガイドとしてご登録いただき、誠にありがとうございます。\n\nご登録いただいた内容を確認させていただき、審査を実施いたします。\n審査完了後、改めてご連絡させていただきます。\n\nご不明な点がございましたら、お気軽にお問い合わせください。\n\n今後ともよろしくお願いいたします。",
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            \App\Models\EmailTemplate::updateOrCreate(
                ['template_key' => $template['template_key']],
                $template
            );
        }

        // メール通知設定の初期データ
        $settings = [
            [
                'notification_type' => 'request',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
            [
                'notification_type' => 'matching',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
            [
                'notification_type' => 'report',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
            [
                'notification_type' => 'reminder',
                'is_enabled' => true,
                'reminder_days' => 3,
            ],
            [
                'notification_type' => 'announcement_reminder',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
            [
                'notification_type' => 'registration',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
            [
                'notification_type' => 'password_reset',
                'is_enabled' => true,
                'reminder_days' => null,
            ],
        ];

        foreach ($settings as $setting) {
            \App\Models\EmailNotificationSetting::updateOrCreate(
                ['notification_type' => $setting['notification_type']],
                $setting
            );
        }
    }
}
