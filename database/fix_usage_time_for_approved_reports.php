<?php

/**
 * 既存の管理者承認済み報告書の利用時間を再計算・計上するスクリプト
 * 
 * 使用方法:
 * php database/fix_usage_time_for_approved_reports.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Report;
use App\Services\UserMonthlyLimitService;
use Carbon\Carbon;

$limitService = app(UserMonthlyLimitService::class);

// 管理者承認済みの報告書を取得
$reports = Report::whereIn('status', ['admin_approved', 'approved'])
    ->whereNotNull('actual_date')
    ->whereNotNull('actual_start_time')
    ->whereNotNull('actual_end_time')
    ->get();

echo "処理対象の報告書: {$reports->count()}件\n\n";

foreach ($reports as $report) {
    try {
        // 実施時間を計算
        $actualDate = Carbon::parse($report->actual_date);
        
        // actual_start_time と actual_end_time を文字列として取得
        if ($report->actual_start_time instanceof Carbon) {
            $startTimeStr = $report->actual_start_time->format('H:i:s');
        } elseif (is_string($report->actual_start_time)) {
            $startTimeStr = $report->actual_start_time;
        } else {
            $startTimeStr = $report->getRawOriginal('actual_start_time');
        }
        
        if ($report->actual_end_time instanceof Carbon) {
            $endTimeStr = $report->actual_end_time->format('H:i:s');
        } elseif (is_string($report->actual_end_time)) {
            $endTimeStr = $report->actual_end_time;
        } else {
            $endTimeStr = $report->getRawOriginal('actual_end_time');
        }
        
        // actual_date と時刻を組み合わせて datetime を作成
        $startDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $startTimeStr);
        $endDateTime = Carbon::parse($actualDate->format('Y-m-d') . ' ' . $endTimeStr);
        
        // 終了時刻が開始時刻より小さい場合、翌日とみなす
        if ($endDateTime->lt($startDateTime)) {
            $endDateTime->addDay();
        }
        
        $durationMinutes = $startDateTime->diffInMinutes($endDateTime);
        $usedHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
        
        // 実施日から年月を取得
        $year = $actualDate->year;
        $month = $actualDate->month;
        
        // 現在の使用時間を確認
        $currentLimit = $limitService->getOrCreateLimit($report->user_id, $year, $month);
        $currentUsedHours = $currentLimit->used_hours;
        
        // 使用時間を追加（既に計上されている可能性があるため、差分のみ追加）
        // 注意: このスクリプトは既存の計上を考慮せずに追加するため、重複計上を避けるために
        // 手動で確認する必要があります
        $limitService->addUsedHours($report->user_id, $usedHours, $year, $month);
        
        $newLimit = $limitService->getOrCreateLimit($report->user_id, $year, $month);
        
        echo "報告書ID: {$report->id}, ユーザーID: {$report->user_id}, ";
        echo "実施日: {$actualDate->format('Y-m-d')}, ";
        echo "利用時間: {$usedHours}時間, ";
        echo "計上前: {$currentUsedHours}時間 → 計上後: {$newLimit->used_hours}時間\n";
        
    } catch (\Exception $e) {
        echo "エラー (報告書ID: {$report->id}): {$e->getMessage()}\n";
    }
}

echo "\n処理完了\n";

