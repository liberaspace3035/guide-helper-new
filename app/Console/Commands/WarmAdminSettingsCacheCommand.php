<?php

namespace App\Console\Commands;

use App\Models\AdminSetting;
use App\Models\EmailNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmAdminSettingsCacheCommand extends Command
{
    protected $signature = 'admin:warm-settings-cache';
    protected $description = '管理者画面の設定用キャッシュを事前に温める（初回リクエストの遅延対策）';

    public function handle(): int
    {
        try {
            Cache::put('admin_email_notification_settings', EmailNotificationSetting::orderBy('notification_type')->get(), 300);
            $autoMatching = AdminSetting::where('setting_key', 'auto_matching')->value('setting_value') === 'true';
            Cache::put('admin_auto_matching', $autoMatching, 300);
            $this->info('管理者設定キャッシュをウォームしました。');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->warn('キャッシュのウォームに失敗しました: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
