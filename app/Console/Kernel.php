<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // リマインドメールは毎分実行し、コマンド内で管理者が設定した送信時刻と一致するときのみ送信
        $schedule->command('emails:send-reminders')->everyMinute();

        // 管理者画面の設定キャッシュを5分ごとにウォーム（初回リクエストの遅延対策）
        $schedule->command('admin:warm-settings-cache')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
