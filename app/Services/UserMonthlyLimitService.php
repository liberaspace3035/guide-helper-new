<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMonthlyLimit;
use App\Models\UserMonthlyLimitRule;
use Carbon\Carbon;

class UserMonthlyLimitService
{
    /**
     * 継続ルールから、指定月に適用されるデフォルト限度時間を取得（該当がなければ null）
     */
    public function getDefaultLimitFromRule(int $userId, int $year, int $month, string $requestType): ?float
    {
        $firstDay = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $rule = UserMonthlyLimitRule::where('user_id', $userId)
            ->where('request_type', $requestType)
            ->where('effective_from', '<=', $firstDay)
            ->orderBy('effective_from', 'desc')
            ->first();
        return $rule !== null ? (float) $rule->limit_hours : null;
    }

    /**
     * 指定月の「実効限度」と「使用時間」を取得（レコードは作らない）
     * 月別設定があればそれを、なければ継続ルールを、どちらもなければ 0。
     */
    public function getEffectiveLimitAndUsed(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): array
    {
        $now = Carbon::now();
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;
        $requestType = in_array($requestType, ['outing', 'home'], true) ? $requestType : 'outing';

        $monthly = UserMonthlyLimit::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('request_type', $requestType)
            ->first();

        if ($monthly !== null) {
            return [
                'limit_hours' => (float) $monthly->limit_hours,
                'used_hours' => (float) $monthly->used_hours,
            ];
        }

        $fromRule = $this->getDefaultLimitFromRule($userId, $year, $month, $requestType);
        return [
            'limit_hours' => $fromRule ?? 0.0,
            'used_hours' => 0.0,
        ];
    }

    /**
     * 指定ユーザーの指定月・依頼種別の限度時間を取得（なければ作成）
     * 新規作成時は継続ルールの値があればそれを、なければ 0 をセットする。
     * @param string $requestType 'outing'=外出, 'home'=自宅
     */
    public function getOrCreateLimit(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): UserMonthlyLimit
    {
        $now = Carbon::now();
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;
        $requestType = in_array($requestType, ['outing', 'home'], true) ? $requestType : 'outing';

        $existing = UserMonthlyLimit::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('request_type', $requestType)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $defaultHours = $this->getDefaultLimitFromRule($userId, $year, $month, $requestType) ?? 0.0;
        return UserMonthlyLimit::create([
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
            'request_type' => $requestType,
            'limit_hours' => $defaultHours,
            'used_hours' => 0.00,
        ]);
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
     * 浮動小数点誤差を避けるため、残時間が必要時間より 0.01 時間以上あれば可とする。
     */
    public function canCreateRequest(int $userId, float $requestHours, int $year = null, int $month = null, string $requestType = 'outing'): bool
    {
        $remaining = $this->getRemainingHours($userId, $year, $month, $requestType);
        return $remaining >= $requestHours - 0.01;
    }

    /**
     * 指定ユーザー・指定月・依頼種別の使用時間を取得
     */
    public function getUsedHours(int $userId, int $year = null, int $month = null, string $requestType = 'outing'): float
    {
        $limit = $this->getOrCreateLimit($userId, $year, $month, $requestType);
        return (float) $limit->used_hours;
    }

    /**
     * 継続ルール保存後に、既存の user_monthly_limits の limit_hours を
     * その月に適用されるルール値へ揃える（一覧・残時間表示と実効値のずれを防ぐ）
     */
    public function syncMonthlyLimitHoursFromRules(int $userId, Carbon $fromMonthStart, int $monthsAhead = 48): void
    {
        $cursor = $fromMonthStart->copy()->startOfMonth();
        for ($i = 0; $i < $monthsAhead; $i++) {
            $y = (int) $cursor->year;
            $m = (int) $cursor->month;
            foreach (['outing', 'home'] as $requestType) {
                $ruleHours = $this->getDefaultLimitFromRule($userId, $y, $m, $requestType);
                if ($ruleHours === null) {
                    continue;
                }
                // 月別行が未作成でもルール値を反映（一覧の表示ずれ防止）
                UserMonthlyLimit::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'year' => $y,
                        'month' => $m,
                        'request_type' => $requestType,
                    ],
                    [
                        'limit_hours' => $ruleHours,
                    ]
                );
            }
            $cursor->addMonth();
        }
    }
}

