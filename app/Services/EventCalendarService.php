<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class EventCalendarService
{
    private const PREFECTURES = [
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ];

    public function splitPlace(?string $prefecture, ?string $place): array
    {
        $pref = trim((string) $prefecture);
        if ($pref !== '' && in_array($pref, self::PREFECTURES, true)) {
            return ['prefecture' => $pref, 'destination_address' => trim((string) $place)];
        }

        $full = trim((string) $place);
        if ($full === '') {
            return ['prefecture' => $pref !== '' ? $pref : '東京都', 'destination_address' => ''];
        }

        foreach (self::PREFECTURES as $p) {
            if (str_starts_with($full, $p)) {
                $rest = trim(mb_substr($full, mb_strlen($p)));
                return ['prefecture' => $p, 'destination_address' => $rest];
            }
        }

        return ['prefecture' => $pref !== '' ? $pref : '東京都', 'destination_address' => $full];
    }

    public function canViewForPrefill(?User $user, Event $event): bool
    {
        if ($event->isPublished()) {
            return true;
        }
        if ($user && $user->isAdmin()) {
            return true;
        }
        if ($user && $event->created_by && (int) $event->created_by === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function toPrefillForRequest(Event $event): array
    {
        $split = $this->splitPlace($event->prefecture, $event->place);
        $start = Carbon::parse($event->start_at);
        $end = $event->end_at ? Carbon::parse($event->end_at) : null;

        $serviceLines = array_filter([$event->title, $event->description]);
        if ($event->url) {
            $serviceLines[] = '関連URL: ' . $event->url;
        }

        return [
            'request_type' => 'outing',
            'prefecture' => $split['prefecture'],
            'destination_address' => $split['destination_address'] !== '' ? $split['destination_address'] : ($split['prefecture'] . '（イベント会場）'),
            'meeting_place' => $split['destination_address'] !== '' ? $split['destination_address'] : ($event->place ?? ''),
            'service_content' => implode("\n", $serviceLines) ?: $event->title,
            'request_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end ? $end->format('H:i') : $start->copy()->addHour()->format('H:i'),
            'event_id' => $event->id,
        ];
    }

    public function toPrefillForProposal(Event $event): array
    {
        $split = $this->splitPlace($event->prefecture, $event->place);
        $start = Carbon::parse($event->start_at);
        $end = $event->end_at ? Carbon::parse($event->end_at) : null;

        $serviceLines = array_filter([$event->title, $event->description]);
        if ($event->url) {
            $serviceLines[] = '関連URL: ' . $event->url;
        }

        return [
            'request_type' => 'outing',
            'prefecture' => $split['prefecture'],
            'destination_address' => $split['destination_address'],
            'meeting_place' => $split['destination_address'] !== '' ? $split['destination_address'] : ($event->place ?? ''),
            'service_content' => implode("\n", $serviceLines) ?: $event->title,
            'proposed_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end ? $end->format('H:i') : $start->copy()->addHour()->format('H:i'),
            'message' => 'イベント「' . $event->title . '」に関連する支援の提案です。',
            'proposal_target' => 'individual',
            'event_id' => $event->id,
        ];
    }

    public function toPrefillForPersonal(Event $event): array
    {
        return [
            'title' => $event->title,
            'prefecture' => $event->prefecture,
            'place' => $event->place,
            'start_at' => $event->start_at->format('Y-m-d\TH:i'),
            'end_at' => $event->end_at ? $event->end_at->format('Y-m-d\TH:i') : '',
            'url' => $event->url,
            'description' => $event->description,
            'event_id' => $event->id,
        ];
    }

    public function eventToPublicArray(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'prefecture' => $event->prefecture,
            'place' => $event->place,
            'start_at' => $event->start_at->toIso8601String(),
            'end_at' => $event->end_at ? $event->end_at->toIso8601String() : null,
            'url' => $event->url,
            'description' => $event->description,
            'status' => $event->status,
        ];
    }
}
