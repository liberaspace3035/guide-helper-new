<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMonthlyLimit;
use Carbon\Carbon;

class UserMonthlyLimitService
{
    /**
     * 指定ユーザーの指定月・依頼種別の限度時間を取得（なければ作成）
     * @param string $requestType 'outing'=外出, 'home'=自宅
     */
    public function getOrCreateLimit(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): UserMonthlyLimit
    {
        $now = Carbon::now();
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;
        $requestType = in_array($requestType, ['outing', 'home'], true) ? $requestType : 'outing';

        return UserMonthlyLimit::firstOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
                'request_type' => $requestType,
            ],
            [
                'limit_hours' => 0.00,
                'used_hours' => 0.00,
            ]
        );
    }

    /**
     * 指定ユーザーの指定月・依頼種別の残時間を取得
     */
    public function getRemainingHours(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): float
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        return max(0, (float) $limit->limit_hours - (float) $limit->used_hours);
    }

    /**
     * 指定ユーザーの指定月・依頼種別の限度時間を設定
     */
    public function setLimit(int $userId, float $limitHours, int $year = null, int $month = null, string $requestType = 'outing'): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        $limit->limit_hours = $limitHours;
        $limit->save();
        return $limit;
    }

    /**
     * 使用時間を追加（報告書確定時）。依頼種別(外出/自宅)に応じて加算先を分ける。
     */
    public function addUsedHours(int $userId, float $hours, int $year = null, int $month = null, string $requestType = 'outing'): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        $limit->used_hours = (float) $limit->used_hours + $hours;
        $limit->save();
        return $limit;
    }

    /**
     * 使用時間を減算（報告書削除時など）
     */
    public function subtractUsedHours(int $userId, float $hours, int $year = null, int $month = null, string $requestType = 'outing'): UserMonthlyLimit
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        $limit->used_hours = max(0, (float) $limit->used_hours - $hours);
        $limit->save();
        return $limit;
    }

    /**
     * 依頼作成可能かチェック（指定種別の残時間が十分か）
     */
    public function canCreateRequest(int $userId, float $requestHours, int $year = null, int $month = null, string $requestType = 'outing'): bool
    {
        $remaining = $this->getRemainingHours($userId, $year, $month, $requestType);
        return $remaining >= $requestHours;
    }

    /**
     * 指定ユーザー・指定月・依頼種別の使用時間を取得
     */
    public function getUsedHours(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): float
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        return (float) $limit->used_hours;
    }
}

