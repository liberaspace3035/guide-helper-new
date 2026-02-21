<?php

namespace App\Services;

use App\Models\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\GuideAcceptance;
use App\Models\Matching;
use App\Models\Notification;
use App\Models\AdminSetting;
use App\Services\UserMonthlyLimitService;
use App\Services\AIInputService;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestService
{
    protected $maskAddressService;
    protected $limitService;
    protected $aiService;
    protected $emailService;

    public function __construct(MaskAddressService $maskAddressService, UserMonthlyLimitService $limitService, AIInputService $aiService, EmailNotificationService $emailService)
    {
        $this->maskAddressService = $maskAddressService;
        $this->limitService = $limitService;
        $this->aiService = $aiService;
        $this->emailService = $emailService;
    }

    /**
     * PostgreSQL の UTF-8 エラーを防ぐため、不正なバイト列を除去して有効な UTF-8 に正規化する。
     * クライアント環境差（IME・貼り付け・音声入力など）で稀に混入する不正バイト列を除去する。
     */
    private function sanitizeUtf8(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (function_exists('iconv')) {
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            return $cleaned !== false ? $cleaned : $value;
        }
        // iconv がない環境: mb_convert_encoding で不正バイトを置換（PHP 7.2+）
        if (function_exists('mb_convert_encoding')) {
            $cleaned = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            return $cleaned !== false ? $cleaned : $value;
        }
        return $value;
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
                
                // 残時間チェック（依頼種別＝外出/自宅ごとの限度で判定）
                $requestType = $data['request_type'] ?? 'outing';
                if (!$this->limitService->canCreateRequest($userId, $requestHours, $year, $month, $requestType)) {
                    $remaining = $this->limitService->getRemainingHours($userId, $year, $month, $requestType);
                    $typeLabel = $requestType === 'home' ? '自宅' : '外出';
                    throw new \Exception("{$typeLabel}の月次限度時間を超過しています。残時間: {$remaining}時間（必要時間: {$requestHours}時間）");
                }
            }
        }

        // 都道府県と市区町村・番地を結合してdestination_addressを作成（UTF-8 正規化で不正バイト列を除去）
        $prefecture = $this->sanitizeUtf8($data['prefecture'] ?? '') ?? '';
        $cityAddress = $this->sanitizeUtf8($data['destination_address'] ?? '') ?? '';
        $fullAddress = $prefecture . $cityAddress;

        // 住所マスキング（マスク結果も正規化）
        $maskedAddress = $this->sanitizeUtf8($this->maskAddressService->maskAddress($fullAddress)) ?? '';

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

        // AI入力補助（音声入力テキストの整形）。入力・出力ともに UTF-8 正規化
        $notes = $this->sanitizeUtf8($data['notes'] ?? null);
        $formattedNotes = null;
        $isVoiceInput = !empty($data['is_voice_input']);
        $serviceContent = $this->sanitizeUtf8($data['service_content'] ?? '') ?? '';

        if ($serviceContent && $isVoiceInput) {
            $notes = $serviceContent; // 音声入力の生テキストを備考として保存
            $formattedNotes = $this->sanitizeUtf8($this->aiService->formatVoiceText($serviceContent));
            $serviceContent = $formattedNotes ?? $serviceContent; // サービス内容にはAI整形後のテキストを保存
        }

        // 待ち合わせ場所も正規化
        $meetingPlace = $this->sanitizeUtf8($data['meeting_place'] ?? null);

        // 依頼作成（DB に渡す文字列はすべて有効な UTF-8）
        $request = Request::create([
            'user_id' => $userId,
            'nominated_guide_id' => $data['nominated_guide_id'] ?? null,
            'request_type' => $data['request_type'],
            'prefecture' => $prefecture,
            'destination_address' => $fullAddress,
            'meeting_place' => $meetingPlace,
            'masked_address' => $maskedAddress,
            'service_content' => $serviceContent,
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
                $this->emailService->sendRequestNotification($nominatedGuide, $this->buildRequestDataForEmail($request));
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

            // 条件チェック
            $matches = true;
            
            // 対応範囲のチェック
            if (!empty($availableAreas)) {
                // 依頼の都道府県を取得（prefectureカラムがあればそれを使用、なければ住所から抽出）
                $requestPrefecture = $request->prefecture ?? $this->maskAddressService->extractPrefecture($request->destination_address);
                
                if ($requestPrefecture) {
                    // ガイドの対応範囲に依頼の都道府県が含まれているかチェック（完全一致）
                    if (!in_array($requestPrefecture, $availableAreas, true)) {
                        $matches = false;
                    }
                }
            }
            
            // 日付・時間帯のチェック（将来実装予定）
            // 現時点では対応範囲のみチェック

            if ($matches) {
                Notification::create([
                    'user_id' => $guide->id,
                    'type' => 'request',
                    'title' => '新しい依頼が作成されました',
                    'message' => "新しい依頼が作成されました。{$request->masked_address}で{$request->request_date} {$time}の依頼です。",
                    'related_id' => $request->id,
                ]);
                $this->emailService->sendRequestNotification($guide, $this->buildRequestDataForEmail($request));
            }
        }
    }

    /**
     * 依頼通知メール用のデータ配列を組み立て
     */
    protected function buildRequestDataForEmail(Request $request): array
    {
        $requestDate = $request->request_date;
        if ($requestDate instanceof \Carbon\Carbon) {
            $requestDate = $requestDate->format('Y-m-d');
        } elseif ($requestDate instanceof \DateTimeInterface) {
            $requestDate = $requestDate->format('Y-m-d');
        } else {
            $requestDate = (string) $requestDate;
        }
        $requestTime = $request->request_time ?? $request->start_time ?? '';
        if ($requestTime instanceof \DateTimeInterface) {
            $requestTime = $requestTime->format('H:i');
        }
        return [
            'id' => $request->id,
            'request_type' => $request->request_type === 'outing' ? '外出' : '自宅',
            'request_date' => $requestDate,
            'request_time' => (string) $requestTime,
            'masked_address' => $request->masked_address ?? '',
        ];
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
        // ガイド情報を取得
        $guide = User::findOrFail($guideId);
        $guideProfile = $guide->guideProfile;
        
        // ガイドの対応範囲を取得
        $availableAreas = [];
        if ($guideProfile) {
            $availableAreas = $guideProfile->available_areas;
            // 配列でない場合の処理
            if (!is_array($availableAreas)) {
                if (is_string($availableAreas)) {
                    $availableAreas = json_decode($availableAreas, true) ?? [];
                } else {
                    $availableAreas = [];
                }
            }
        }
        
        // デバッグログ：ガイド情報と対応範囲を確認
        \Log::info('ガイドの依頼一覧取得（詳細デバッグ）', [
            'guide_id' => $guideId,
            'guide_email' => $guide->email,
            'guide_profile_exists' => $guideProfile !== null,
            'available_areas_raw' => $guideProfile ? $guideProfile->getRawOriginal('available_areas') : null,
            'available_areas_parsed' => $availableAreas,
            'available_areas_type' => gettype($availableAreas),
            'available_areas_count' => is_array($availableAreas) ? count($availableAreas) : 'not_array',
        ]);
        
        // ガイドが応募済みの依頼IDを取得（ステータス情報も含む）
        $acceptances = GuideAcceptance::where('guide_id', $guideId)
            ->whereIn('status', ['pending', 'matched', 'declined'])
            ->pluck('status', 'request_id')
            ->toArray();

        // 利用可能な依頼（pending または guide_accepted ステータス）
        // 指名ガイドが設定されていない依頼、またはこのガイドが指名されている依頼のみを取得
        // キャンセルされた依頼は除外
        $requests = Request::whereIn('status', ['pending', 'guide_accepted'])
            ->where('status', '!=', 'cancelled') // キャンセルされた依頼を除外
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
        
        // 各依頼に応募済み情報を追加し、対応範囲外の依頼を除外
        $filteredRequests = $requests->filter(function ($request) use ($availableAreas, $guideId) {
            // 指名ガイドの場合は対応範囲チェックをスキップ
            if ($request->nominated_guide_id == $guideId) {
                return true;
            }
            
            // 対応範囲が設定されていない場合はすべて表示
            if (empty($availableAreas)) {
                return true;
            }
            
            // 依頼の都道府県を取得（prefectureカラムがあればそれを使用、なければ住所から抽出）
            $requestPrefecture = $request->prefecture ?? $this->maskAddressService->extractPrefecture($request->destination_address);
            
            if (!$requestPrefecture) {
                // 都道府県が取得できない場合は表示（安全側に倒す）
                // 古いデータでprefectureカラムがnullの場合も表示
                return true;
            }
            
            // ガイドの対応範囲に依頼の都道府県が含まれているかチェック（完全一致）
            $isInArea = in_array($requestPrefecture, $availableAreas, true);
            
            \Log::info('ガイドの依頼一覧フィルタリング（詳細）', [
                'request_id' => $request->id,
                'guide_id' => $guideId,
                'request_prefecture_column' => $request->prefecture,
                'request_destination_address' => $request->destination_address,
                'extracted_prefecture' => $requestPrefecture,
                'available_areas' => $availableAreas,
                'is_in_array' => $isInArea,
                'will_include' => $isInArea,
            ]);
            
            return $isInArea;
        });
        
        \Log::info('フィルタリング結果', [
            'guide_id' => $guideId,
            'total_before_filter' => $requests->count(),
            'total_after_filter' => $filteredRequests->count(),
            'filtered_request_ids' => $filteredRequests->pluck('id')->toArray(),
        ]);
        
        return $filteredRequests->map(function ($request) use ($guideId, $acceptances, $autoMatching) {
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
        })->values(); // インデックスをリセットしてJSONシリアライズを確実にする
    }

    /**
     * 依頼をキャンセル
     * マッチング成立済みの依頼はキャンセル不可
     */
    public function cancelRequest(int $requestId, int $userId): Request
    {
        $request = Request::findOrFail($requestId);

        // 権限チェック（依頼作成者のみキャンセル可能）
        if ($request->user_id !== $userId) {
            throw new \Exception('この依頼をキャンセルする権限がありません');
        }

        // 既にキャンセル済みの場合はエラー
        if ($request->status === 'cancelled') {
            throw new \Exception('この依頼は既にキャンセルされています');
        }

        // マッチング成立済み（matched または in_progress）の場合はキャンセル不可
        if (in_array($request->status, ['matched', 'in_progress'])) {
            throw new \Exception('マッチング成立済みの依頼はキャンセルできません');
        }

        // マッチングが存在する場合は確認
        $matching = Matching::where('request_id', $requestId)
            ->whereIn('status', ['matched', 'in_progress'])
            ->first();

        if ($matching) {
            throw new \Exception('マッチング成立済みの依頼はキャンセルできません');
        }

        // 依頼をキャンセル状態に更新
        $request->update([
            'status' => 'cancelled',
        ]);

        // 関連するGuideAcceptanceを削除（pending状態のみ）
        GuideAcceptance::where('request_id', $requestId)
            ->where('status', 'pending')
            ->delete();

        \Log::info('依頼がキャンセルされました', [
            'request_id' => $requestId,
            'user_id' => $userId,
        ]);

        return $request;
    }
}

