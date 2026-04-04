<?php

namespace App\Services;

use App\Models\Request;
use Carbon\Carbon;

class GuideAvailabilityService
{
    /**
     * 依頼の日時範囲 [開始, 終了) 相当の重なり判定用に両端を含む閉区間として扱う。
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function requestDateTimeRange(Request $request): ?array
    {
        $date = $request->request_date;
        if ($date instanceof \Carbon\CarbonInterface) {
            $dateStr = $date->format('Y-m-d');
        } elseif ($date instanceof \DateTimeInterface) {
            $dateStr = $date->format('Y-m-d');
        } else {
            $dateStr = (string) $date;
        }

        $startTime = $request->start_time ?? $request->request_time ?? null;
        if ($startTime === null || $startTime === '') {
            return null;
        }

        $startTime = self::normalizeTimeString((string) $startTime);
        $endTime = $request->end_time ? self::normalizeTimeString((string) $request->end_time) : null;

        $tz = config('app.timezone');
        $start = Carbon::parse($dateStr . ' ' . $startTime, $tz);

        if ($endTime) {
            $end = Carbon::parse($dateStr . ' ' . $endTime, $tz);
            if ($end->lte($start)) {
                $end = $end->copy()->addDay();
            }
        } else {
            $end = $start->copy()->addHour();
        }

        return [$start, $end];
    }

    public static function normalizeTimeString(string $time): string
    {
        if (str_contains($time, ' ')) {
            $time = explode(' ', $time)[1] ?? $time;
        }
        if (str_contains($time, 'T')) {
            $time = explode('T', $time)[1] ?? $time;
        }
        if (substr_count($time, ':') === 2) {
            $parts = explode(':', $time);
            $time = $parts[0] . ':' . $parts[1];
        }

        return $time;
    }

    /**
     * 依頼時間帯とスロットが1秒でも重なるか
     */
    public static function overlaps(Carbon $reqStart, Carbon $reqEnd, Carbon $slotStart, Carbon $slotEnd): bool
    {
        return $reqStart->lt($slotEnd) && $slotStart->lt($reqEnd);
    }

    /**
     * @param  iterable<int, \App\Models\GuideAvailabilitySlot>  $slots
     */
    public static function requestOverlapsAnySlot(Request $request, iterable $slots): bool
    {
        $range = self::requestDateTimeRange($request);
        if (!$range) {
            return false;
        }
        [$reqStart, $reqEnd] = $range;

        foreach ($slots as $slot) {
            $slotStart = $slot->start_at instanceof Carbon ? $slot->start_at : Carbon::parse($slot->start_at);
            $slotEnd = $slot->end_at
                ? ($slot->end_at instanceof Carbon ? $slot->end_at : Carbon::parse($slot->end_at))
                : $slotStart->copy()->addHour();

            if ($slotEnd->lte($slotStart)) {
                $slotEnd = $slotStart->copy()->addHour();
            }

            if (self::overlaps($reqStart, $reqEnd, $slotStart, $slotEnd)) {
                return true;
            }
        }

        return false;
    }
}
