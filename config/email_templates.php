<?php

return [
    /*
    |--------------------------------------------------------------------------
    | メールテンプレート トリガー一覧
    |--------------------------------------------------------------------------
    | template_key => [ 表示名, 送信タイミング説明, 宛先(user/guide/both), 通知種別, 送信時刻を設定可能か ]
    | 送信時刻を設定可能: reminder / announcement_reminder は毎日指定時刻に送信されるため、scheduled_time で指定可能
    */
    'triggers' => [
        'request_notification' => [
            'label' => '依頼通知',
            'description' => '新しい依頼が登録された時',
            'recipient' => 'guide',
            'notification_type' => 'request',
            'uses_scheduled_time' => false,
        ],
        'matching_notification' => [
            'label' => 'ガイド確定の通知',
            'description' => 'ガイドが確定した時',
            'recipient' => 'both',
            'notification_type' => 'matching',
            'uses_scheduled_time' => false,
        ],
        'report_submitted' => [
            'label' => '報告書提出通知',
            'description' => '報告書が提出された時',
            'recipient' => 'user',
            'notification_type' => 'report',
            'uses_scheduled_time' => false,
        ],
        'report_approved' => [
            'label' => '報告書承認通知',
            'description' => '報告書が承認された時',
            'recipient' => 'guide',
            'notification_type' => 'report',
            'uses_scheduled_time' => false,
        ],
        'reminder_pending_request' => [
            'label' => '承認待ち依頼リマインド',
            'description' => '承認待ち依頼がある時（指定時刻に毎日送信）',
            'recipient' => 'user',
            'notification_type' => 'reminder',
            'uses_scheduled_time' => true,
        ],
        'reminder_same_day' => [
            'label' => '当日リマインド',
            'description' => '依頼当日のリマインド（指定時刻に送信）',
            'recipient' => 'both',
            'notification_type' => 'reminder',
            'uses_scheduled_time' => true,
        ],
        'reminder_day_before' => [
            'label' => '前日リマインド',
            'description' => '依頼前日のリマインド（指定時刻に送信）',
            'recipient' => 'both',
            'notification_type' => 'reminder',
            'uses_scheduled_time' => true,
        ],
        'reminder_report_missing' => [
            'label' => '報告書未提出リマインド',
            'description' => '報告書が未提出の場合のリマインド（指定時刻に送信）',
            'recipient' => 'guide',
            'notification_type' => 'reminder',
            'uses_scheduled_time' => true,
        ],
        'announcement_reminder_unread' => [
            'label' => 'お知らせ未読リマインド',
            'description' => '未読お知らせがある場合のリマインド（指定時刻に毎日送信）',
            'recipient' => 'both',
            'notification_type' => 'announcement_reminder',
            'uses_scheduled_time' => true,
        ],
        'password_reset' => [
            'label' => 'パスワードリセット',
            'description' => 'パスワードリセットがリクエストされた時',
            'recipient' => 'both',
            'notification_type' => 'password_reset',
            'uses_scheduled_time' => false,
        ],
        'user_registration_thanks' => [
            'label' => 'ユーザー登録お礼',
            'description' => 'ユーザー登録が完了した時',
            'recipient' => 'user',
            'notification_type' => 'registration',
            'uses_scheduled_time' => false,
        ],
        'guide_registration_thanks' => [
            'label' => 'ガイド登録お礼',
            'description' => 'ガイド登録が完了した時',
            'recipient' => 'guide',
            'notification_type' => 'registration',
            'uses_scheduled_time' => false,
        ],
        'report_revision_requested' => [
            'label' => '報告書修正依頼（ユーザー→ガイド）',
            'description' => 'ユーザーが報告書に修正依頼した時',
            'recipient' => 'guide',
            'notification_type' => 'report',
            'uses_scheduled_time' => false,
        ],
        'report_admin_revision_requested' => [
            'label' => '報告書差し戻し（管理者→ガイド）',
            'description' => '管理者が報告書を差し戻した時',
            'recipient' => 'guide',
            'notification_type' => 'report',
            'uses_scheduled_time' => false,
        ],
        'user_account_approved' => [
            'label' => 'アカウント承認通知（利用者）',
            'description' => '管理者が利用者を承認した時',
            'recipient' => 'user',
            'notification_type' => 'approval',
            'uses_scheduled_time' => false,
        ],
        'guide_account_approved' => [
            'label' => 'アカウント承認通知（ガイド）',
            'description' => '管理者がガイドを承認した時',
            'recipient' => 'guide',
            'notification_type' => 'approval',
            'uses_scheduled_time' => false,
        ],
    ],
];
