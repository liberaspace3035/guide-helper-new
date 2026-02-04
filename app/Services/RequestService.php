<?php

namespace App\Services;

use App\Models\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\GuideAcceptance;
use App\Models\Notification;
use App\Models\AdminSetting;
use App\Services\UserMonthlyLimitService;
use App\Services\AIInputService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestService
{
    protected $maskAddressService;
    protected $limitService;
    protected $aiService;

    public function __construct(MaskAddressService $maskAddressService, UserMonthlyLimitService $limitService, AIInputService $aiService)
    {
        $this->maskAddressService = $maskAddressService;
        $this->limitService = $limitService;
        $this->aiService = $aiService;
    }

    public function createRequest(array $data, int $userId): Request
    {
        // 承認待ちの報告書がある場合は新規依頼を作成できない
        $pendingReports = Report::where('user_id', $userId)
            ->where('status', 'submitted')
            ->exists();

        if ($pendingReports) {
            throw new \Exception('承認待ちの報告書があります。承認または修正依頼を完了してから新しい依頼を作成してください');
        }

        // 利用者の限度時間チェック
        $user = User::findOrFail($userId);
        if ($user->role === 'user') {
            // 依頼時間を計算（分単位）
            $startTime = $data['start_time'] ?? $data['request_time'] ?? null;
            $endTime = $data['end_time'] ?? null;
            
            if ($startTime && $endTime) {
                // 時刻を分単位に変換
                list($startHour, $startMinute) = explode(':', $startTime);
                list($endHour, $endMinute) = explode(':', $endTime);
                
                $startMinutes = (int)$startHour * 60 + (int)$startMinute;
                $endMinutes = (int)$endHour * 60 + (int)$endMinute;
                
                // 終了時刻が開始時刻より小さい場合、翌日とみなす
                if ($endMinutes < $startMinutes) {
                    $endMinutes += 24 * 60;
                }
                
                $durationMinutes = $endMinutes - $startMinutes;
                $requestHours = round($durationMinutes / 60 * 10) / 10; // 小数点第1位まで
                
                // 依頼日から年月を取得
                $requestDate = Carbon::parse($data['request_date']);
                $year = $requestDate->year;
                $month = $requestDate->month;
                
                // 残時間チェック
                if (!$this->limitService->canCreateRequest($userId, $requestHours, $year, $month)) {
                    $remaining = $this->limitService->getRemainingHours($userId, $year, $month);
                    throw new \Exception("月次限度時間を超過しています。残時間: {$remaining}時間（必要時間: {$requestHours}時間）");
                }
            }
        }

        // 住所マスキング
        $maskedAddress = $this->maskAddressService->maskAddress($data['destination_address']);

        // 日付形式の正規化（DATE型用）
        $requestDate = $data['request_date'] ?? null;
        if ($requestDate && strpos($requestDate, ' ') !== false) {
            $requestDate = explode(' ', $requestDate)[0];
        }

        // 時刻形式の正規化（TIME型用）
        $startTime = $data['start_time'] ?? $data['request_time'] ?? null;
        if ($startTime) {
            // DATETIME形式の場合はTIME部分のみを抽出
            if (strpos($startTime, ' ') !== false) {
                $startTime = explode(' ', $startTime)[1] ?? $startTime;
            }
            // ISO形式（T区切り）の場合は時刻部分のみを抽出
            if (strpos($startTime, 'T') !== false) {
                $startTime = explode('T', $startTime)[1] ?? $startTime;
            }
            // 秒部分がある場合は除去（HH:MM:SS → HH:MM）
            if (substr_count($startTime, ':') === 2) {
                $parts = explode(':', $startTime);
                $startTime = $parts[0] . ':' . $parts[1];
            }
        }

        $endTime = $data['end_time'] ?? null;
        if ($endTime) {
            // DATETIME形式の場合はTIME部分のみを抽出
            if (strpos($endTime, ' ') !== false) {
                $endTime = explode(' ', $endTime)[1] ?? $endTime;
            }
            // ISO形式（T区切り）の場合は時刻部分のみを抽出
            if (strpos($endTime, 'T') !== false) {
                $endTime = explode('T', $endTime)[1] ?? $endTime;
            }
            // 秒部分がある場合は除去（HH:MM:SS → HH:MM）
            if (substr_count($endTime, ':') === 2) {
                $parts = explode(':', $endTime);
                $endTime = $parts[0] . ':' . $parts[1];
            }
        }

        $requestTime = $data['request_time'] ?? $startTime;
        if ($requestTime) {
            // DATETIME形式の場合はTIME部分のみを抽出
            if (strpos($requestTime, ' ') !== false) {
                $requestTime = explode(' ', $requestTime)[1] ?? $requestTime;
            }
            // ISO形式（T区切り）の場合は時刻部分のみを抽出
            if (strpos($requestTime, 'T') !== false) {
                $requestTime = explode('T', $requestTime)[1] ?? $requestTime;
            }
            // 秒部分がある場合は除去（HH:MM:SS → HH:MM）
            if (substr_count($requestTime, ':') === 2) {
                $parts = explode(':', $requestTime);
                $requestTime = $parts[0] . ':' . $parts[1];
            }
        }

        // AI入力補助（音声入力テキストの整形）
        $notes = $data['notes'] ?? null;
        $formattedNotes = null;
        $isVoiceInput = $data['is_voice_input'] ?? false;
        
        if ($notes && $isVoiceInput) {
            $formattedNotes = $this->aiService->formatVoiceText($notes);
        }

        // 依頼作成
        $request = Request::create([
            'user_id' => $userId,
            'nominated_guide_id' => $data['nominated_guide_id'] ?? null,
            'request_type' => $data['request_type'],
            'destination_address' => $data['destination_address'],
            'meeting_place' => $data['meeting_place'] ?? null,
            'masked_address' => $maskedAddress,
            'service_content' => $data['service_content'],
            'request_date' => $requestDate,
            'request_time' => $requestTime,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $data['duration'] ?? null,
            'notes' => $notes,
            'formatted_notes' => $formattedNotes,
            'guide_gender' => $data['guide_gender'] ?? null,
            'guide_age' => $data['guide_age'] ?? null,
            'status' => 'pending',
        ]);

        // 条件に合致するガイドに通知
        $this->notifyMatchingGuides($request);

        return $request;
    }

    protected function notifyMatchingGuides(Request $request)
    {
        // リクエストの日付から曜日を取得
        $date = new \DateTime($request->request_date);
        $dayOfWeek = (int)$date->format('w');
        $dayType = ($dayOfWeek === 0 || $dayOfWeek === 6) ? '土日' : '平日';

        // 時刻から時間帯を判定
        $time = $request->start_time ?? $request->request_time;
        $hour = (int)explode(':', $time)[0];
        $timeType = '午後';
        if ($hour < 12) {
            $timeType = '午前';
        } else if ($hour >= 18) {
            $timeType = '夜間';
        }

        // 指名ガイドが指定されている場合、そのガイドに優先的に通知
        if ($request->nominated_guide_id) {
            $nominatedGuide = User::where('id', $request->nominated_guide_id)
                ->where('role', 'guide')
                ->where('is_allowed', true)
                ->first();
            
            if ($nominatedGuide) {
                Notification::create([
                    'user_id' => $nominatedGuide->id,
                    'type' => 'request',
                    'title' => '指名依頼が作成されました',
                    'message' => "あなたが指名された依頼が作成されました。{$request->masked_address}で{$request->request_date} {$time}の依頼です。",
                    'related_id' => $request->id,
                ]);
                // 指名ガイドが指定されている場合は、そのガイドのみに通知して終了
                return;
            }
        }

        // 条件に合致するガイドを検索
        $guides = User::where('role', 'guide')
            ->where('is_allowed', true)
            ->with('guideProfile')
            ->get();

        foreach ($guides as $guide) {
            if (!$guide->guideProfile) continue;

            // GuideProfileモデルで'array'としてキャストされているため、通常は既に配列として取得される
            // ただし、念のため文字列の場合はjson_decode()を呼ぶ
            $availableAreas = $guide->guideProfile->available_areas;
            $availableDays = $guide->guideProfile->available_days;
            $availableTimes = $guide->guideProfile->available_times;
            
            // 配列でない場合の処理
            if (!is_array($availableAreas)) {
                if (is_string($availableAreas)) {
                    $availableAreas = json_decode($availableAreas, true) ?? [];
                } else {
                    $availableAreas = [];
                }
            }
            if (!is_array($availableDays)) {
                if (is_string($availableDays)) {
                    $availableDays = json_decode($availableDays, true) ?? [];
                } else {
                    $availableDays = [];
                }
            }
            if (!is_array($availableTimes)) {
                if (is_string($availableTimes)) {
                    $availableTimes = json_decode($availableTimes, true) ?? [];
                } else {
                    $availableTimes = [];
                }
            }

            // 条件チェック（簡易版）
            $matches = true;
            // 実際の条件チェックは後で実装

            if ($matches) {
                Notification::create([
                    'user_id' => $guide->id,
                    'type' => 'request',
                    'title' => '新しい依頼が作成されました',
                    'message' => "新しい依頼が作成されました。{$request->masked_address}で{$request->request_date} {$time}の依頼です。",
                    'related_id' => $request->id,
                ]);
            }
        }
    }

    public function getUserRequests(int $userId)
    {
        return Request::where('user_id', $userId)
            ->with([
                'matching' => function($query) {
                    $query->select('id', 'request_id', 'guide_id', 'status', 'matched_at')
                          ->whereNull('report_completed_at'); // 完了済みを除外
                },
                'matching.guide:id,name',
                'matching.guide.guideProfile:id,user_id,introduction'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function getAvailableRequestsForGuide(int $guideId)
    {
        // ガイドが応募済みの依頼IDを取得（ステータス情報も含む）
        $acceptances = GuideAcceptance::where('guide_id', $guideId)
            ->whereIn('status', ['pending', 'matched', 'declined'])
            ->pluck('status', 'request_id')
            ->toArray();

        // 利用可能な依頼（pending または guide_accepted ステータス）
        // 指名ガイドが設定されていない依頼、またはこのガイドが指名されている依頼のみを取得
        $requests = Request::whereIn('status', ['pending', 'guide_accepted'])
            ->where(function($query) use ($guideId) {
                // 指名ガイドが設定されていない依頼、またはこのガイドが指名されている依頼
                $query->whereNull('nominated_guide_id')
                      ->orWhere('nominated_guide_id', $guideId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 自動マッチング設定を確認
        $autoMatching = AdminSetting::where('setting_key', 'auto_matching')
            ->value('setting_value') === 'true';
        
        // 各依頼に応募済み情報を追加
        return $requests->map(function ($request) use ($guideId, $acceptances, $autoMatching) {
            $hasApplied = isset($acceptances[$request->id]);
            $acceptanceStatus = $acceptances[$request->id] ?? null;
            
            // 応募済みで自動マッチングが無効の場合、ステータス表示を「承認待ち」に
            if ($hasApplied && $acceptanceStatus === 'pending' && !$autoMatching) {
                $displayStatus = 'approval_pending'; // 承認待ち
            } else {
                $displayStatus = $request->status;
            }
            
            // 配列に変換して動的プロパティを追加（JSONシリアライズ時に含まれるようにする）
            $requestArray = $request->toArray();
            $requestArray['has_applied'] = $hasApplied;
            $requestArray['acceptance_status'] = $acceptanceStatus;
            $requestArray['display_status'] = $displayStatus;
            
            // オブジェクトとして返すために stdClass に変換
            return (object) $requestArray;
        });
    }
}

