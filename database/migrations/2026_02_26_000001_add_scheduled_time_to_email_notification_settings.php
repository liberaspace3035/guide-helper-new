<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 各メール通知の送信時刻を手動設定するためのカラム追加
     */
    public function up(): void
    {
        Schema::table('email_notification_settings', function (Blueprint $table) {
            $table->string('scheduled_time', 5)->nullable()->after('reminder_days')
                ->comment('送信時刻（HH:MM）。スケジュール送信する通知種別で使用');
        });

        // リマインドは従来 09:00 のためデフォルトを設定
        \Illuminate\Support\Facades\DB::table('email_notification_settings')
            ->where('notification_type', 'reminder')
            ->whereNull('scheduled_time')
            ->update(['scheduled_time' => '09:00']);
    }

    public function down(): void
    {
        Schema::table('email_notification_settings', function (Blueprint $table) {
            $table->dropColumn('scheduled_time');
        });
    }
};
