<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMonthlyLimit;
use Carbon\Carbon;

class UserMonthlyLimitService
{
    /**
     * 指定ユーザーの指定月の限度時間を取得（なければ作成）
     */
    public function getOrCreateLimit(int $userId, int $year = null, int $month = null): UserMonthlyLimit
    {
        $now = Carbon::now();
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;

        return UserMonthlyLimit::firstOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'limit_hours' => 0.00,
                'used_hours' => 0.00,
            ]
        );
    }

    /**
     * 指定ユーザーの指定月の残時間を取得
     */
    public function getRemainingHours(int $userId, int $year = null, int $month = null): float
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month);
        return max(0, $limit->limit_hours - $limit->used_hours);
    }

    /**
     * 指定ユーザーの指定月の限度時間を設定
     */
    public function setLimit(int $userId, float $limitHours, int $year = null, int $month = null): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month);
        $limit->limit_hours = $limitHours;
        $limit->save();
        return $limit;
    }

    /**
     * 使用時間を追加（報告書確定時）
     */
    public function addUsedHours(int $userId, float $hours, int $year = null, int $month = null): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month);
        $limit->used_hours += $hours;
        $limit->save();
        return $limit;
    }

    /**
     * 使用時間を減算（報告書削除時など）
     */
    public function subtractUsedHours(int $userId, float $hours, int $year = null, int $month = null): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month);
        $limit->used_hours = max(0, $limit->used_hours - $hours);
        $limit->save();
        return $limit;
    }

    /**
     * 依頼作成可能かチェック（残時間が十分か）
     */
    public function canCreateRequest(int $userId, float $requestHours, int $year = null, int $month = null): bool
    {
        $remaining = $this->getRemainingHours($userId, $year, $month);
        return $remaining >= $requestHours;
    }

    /**
     * 指定ユーザー・指定月の使用時間を取得
     * （管理画面の履歴表示などで利用）
     */
    public function getUsedHours(int $userId, int $year = null, int $month = null): float
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month);
        return (float) $limit->used_hours;
    }
}

