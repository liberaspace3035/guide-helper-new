<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * アカウント承認通知設定（approval）が存在しない場合は追加する
     */
    public function up(): void
    {
        $exists = DB::table('email_notification_settings')
            ->where('notification_type', 'approval')
            ->exists();

        if (!$exists) {
            DB::table('email_notification_settings')->insert([
                'notification_type' => 'approval',
                'is_enabled' => true,
                'reminder_days' => null,
                'scheduled_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('admin_email_notification_settings');
    }

    public function down(): void
    {
        // オプション: approval を削除する場合はコメントを外す
        // DB::table('email_notification_settings')->where('notification_type', 'approval')->delete();
    }
};
