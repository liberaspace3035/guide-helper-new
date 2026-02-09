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
                'template_key' => 'user_registration_thanks',
                'subject' => 'ご登録ありがとうございます',
                'body' => '{{user_name}} 様\n\nこの度は、ガイドヘルパーマッチングサービスにご登録いただき、誠にありがとうございます。\n\nご登録いただいた内容を確認させていただき、審査を実施いたします。\n審査完了後、改めてご連絡させていただきます。\n\nご不明な点がございましたら、お気軽にお問い合わせください。\n\n今後ともよろしくお願いいたします。',
                'is_active' => true,
            ],
            [
                'template_key' => 'guide_registration_thanks',
                'subject' => 'ご登録ありがとうございます',
                'body' => '{{user_name}} 様\n\nこの度は、ガイドヘルパーマッチングサービスにご登録いただき、誠にありがとうございます。\n\nご登録いただいた内容を確認させていただき、審査を実施いたします。\n審査完了後、改めてご連絡させていただきます。\n\nご不明な点がございましたら、お気軽にお問い合わせください。\n\n今後ともよろしくお願いいたします。',
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_same_day',
                'subject' => '本日の依頼について',
                'body' => '{{user_name}} 様\n\n本日、以下の依頼が予定されています。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\nお時間になりましたら、ご対応をお願いいたします。',
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_day_before',
                'subject' => '明日の依頼について',
                'body' => '{{user_name}} 様\n\n明日、以下の依頼が予定されています。\n\n依頼ID: {{request_id}}\n依頼タイプ: {{request_type}}\n依頼日時: {{request_date}} {{request_time}}\n場所: {{masked_address}}\n\nお時間になりましたら、ご対応をお願いいたします。',
                'is_active' => true,
            ],
            [
                'template_key' => 'reminder_report_missing',
                'subject' => '報告書の提出をお願いします',
                'body' => '{{guide_name}} 様\n\n以下の報告書がまだ提出されていません。\n\n依頼ID: {{request_id}}\n実施日: {{actual_date}}\n\n報告書の提出をお願いいたします。',
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
        ];

        foreach ($settings as $setting) {
            \App\Models\EmailNotificationSetting::updateOrCreate(
                ['notification_type' => $setting['notification_type']],
                $setting
            );
        }
    }
}
