<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\PersonalCalendarEntry;
use App\Services\RequestService;
use App\Services\UserMonthlyLimitService;
use App\Services\EventCalendarService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RequestController extends Controller
{
    protected $requestService;
    protected $limitService;
    protected EventCalendarService $eventCalendarService;

    public function __construct(RequestService $requestService, UserMonthlyLimitService $limitService, EventCalendarService $eventCalendarService)
    {
        $this->requestService = $requestService;
        $this->limitService = $limitService;
        $this->eventCalendarService = $eventCalendarService;
    }

    public function index()
    {
        $user = Auth::user();
        
        $requests = $this->requestService->getUserRequests($user->id);
        
        return view('requests.index', [
            'requests' => $requests,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $remainingHours = null;
        if ($user->isUser()) {
            $user->load('userProfile');
            $introduction = trim($user->userProfile->introduction ?? '');
            if ($introduction === '') {
                return redirect()->route('profile')
                    ->withErrors(['error' => '依頼を作成するには、プロフィールの自己PR（自己紹介）の入力が必要です。下記の「自己PR（自己紹介）」欄に入力してください。']);
            }
            $now = Carbon::now();
            $remainingHours = [
                'outing' => round($this->limitService->getRemainingHours($user->id, $now->year, $now->month, 'outing'), 1),
                'home' => round($this->limitService->getRemainingHours($user->id, $now->year, $now->month, 'home'), 1),
                'year' => $now->year,
                'month' => $now->month,
            ];
        }
        $prefillEvent = null;
        if (request()->filled('event_id')) {
            $event = Event::find((int) request('event_id'));
            if ($event && $this->eventCalendarService->canViewForPrefill($user, $event)) {
                $prefillEvent = $this->eventCalendarService->toPrefillForRequest($event);
            }
        } elseif ($user->isUser() && request()->filled('personal_entry_id')) {
            $entry = PersonalCalendarEntry::where('user_id', $user->id)->find((int) request('personal_entry_id'));
            if ($entry) {
                $prefillEvent = $this->eventCalendarService->toPrefillForRequestFromPersonal($entry);
            }
        }

        return view('requests.create', [
            'remainingHours' => $remainingHours,
            'prefillEvent' => $prefillEvent,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->isUser()) {
            $user->load('userProfile');
            if (trim($user->userProfile->introduction ?? '') === '') {
                return redirect()->route('profile')
                    ->withErrors(['error' => '依頼を作成するには、プロフィールの自己PR（自己紹介）の入力が必要です。プロフィールから入力してください。']);
            }
        }

        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        ];
        
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|in:outing,home',
            'prefecture' => 'required|string|in:' . implode(',', $prefectures),
            'destination_address' => 'required|string',
            'meeting_place' => 'required_if:request_type,outing|nullable|string',
            'service_content' => 'required|string',
            'request_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'guide_gender' => 'nullable|in:none,male,female',
            'guide_age' => 'nullable|in:none,20s,30s,40s,50s,60s',
            // 繰り返し設定のバリデーション
            'repeat_enabled' => 'nullable|in:0,1',
            'repeat_type' => 'nullable|in:weekly,monthly,custom',
            'repeat_interval' => 'nullable|integer|min:1|max:4',
            'repeat_weekdays' => 'nullable|string',
            'repeat_until' => 'nullable|date|after_or_equal:request_date',
            'repeat_month_count' => 'nullable|integer|min:2|max:6',
            'repeat_custom_dates' => 'nullable|string',
        ], [
            'request_type.required' => '依頼タイプを選択してください',
            'request_type.in' => '依頼タイプが不正です',
            'prefecture.required' => '都道府県を選択してください',
            'prefecture.in' => '都道府県が不正です',
            'destination_address.required' => '市区町村・番地を入力してください',
            'destination_address.string' => '市区町村・番地は文字列で入力してください',
            'meeting_place.required_if' => '待ち合わせ場所（集合場所）を入力してください',
            'meeting_place.string' => '待ち合わせ場所（集合場所）は文字列で入力してください',
            'service_content.required' => 'サービス内容を入力してください',
            'service_content.string' => 'サービス内容は文字列で入力してください',
            'request_date.required' => '希望日を選択してください',
            'request_date.date' => '希望日の形式が不正です',
            'start_time.required' => '開始時刻を選択してください',
            'start_time.date_format' => '開始時刻の形式が不正です',
            'end_time.required' => '終了時刻を選択してください',
            'end_time.date_format' => '終了時刻の形式が不正です',
            'guide_gender.in' => '希望するガイドの性別が不正です',
            'guide_age.in' => '希望するガイドの年代が不正です',
            'repeat_type.in' => '繰り返しパターンが不正です',
            'repeat_interval.min' => '繰り返し頻度は1以上を指定してください',
            'repeat_interval.max' => '繰り返し頻度は4以下を指定してください',
            'repeat_until.after_or_equal' => '終了日は希望日以降を指定してください',
            'repeat_month_count.min' => '繰り返し回数は2ヶ月以上を指定してください',
            'repeat_month_count.max' => '繰り返し回数は6ヶ月以下を指定してください',
        ]);

        if ($validator->fails()) {
            \Log::info('依頼作成バリデーションエラー', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all()
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 終了時刻が開始時刻より後であることを確認（日付をまたぐ場合も考慮）
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        
        // 時刻を分単位に変換
        list($startHour, $startMinute) = explode(':', $startTime);
        list($endHour, $endMinute) = explode(':', $endTime);
        
        $startMinutes = (int)$startHour * 60 + (int)$startMinute;
        $endMinutes = (int)$endHour * 60 + (int)$endMinute;
        
        // 終了時刻が開始時刻より小さい場合、翌日とみなす（24時間を加算）
        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60; // 24時間 = 1440分を加算
        } elseif ($endMinutes === $startMinutes) {
            // 開始時刻と終了時刻が同じ場合はエラー
            return redirect()->back()
                ->withErrors(['end_time' => '終了時刻は開始時刻より後である必要があります'])
                ->withInput();
        }
        
        // 実際の時間差を計算
        $durationMinutes = $endMinutes - $startMinutes;
        
        // 24時間（1440分）を超える場合はエラー
        if ($durationMinutes > 24 * 60) {
            return redirect()->back()
                ->withErrors(['end_time' => '依頼時間は24時間以内である必要があります'])
                ->withInput();
        }
        
        // 時間差が0以下の場合はエラー（念のため）
        if ($durationMinutes <= 0) {
            return redirect()->back()
                ->withErrors(['end_time' => '終了時刻は開始時刻より後である必要があります'])
                ->withInput();
        }

        // 外出依頼の場合、待ち合わせ場所が必須
        if ($request->request_type === 'outing' && !$request->meeting_place) {
            return redirect()->back()
                ->withErrors(['meeting_place' => '待ち合わせ場所を入力してください'])
                ->withInput();
        }

        try {
            \Log::info('依頼作成開始', [
                'user_id' => Auth::id(),
                'request_date' => $request->request_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'repeat_enabled' => $request->repeat_enabled,
            ]);
            
            $data = $request->all();
            
            // 日付形式の正規化（DATE型用）
            if (isset($data['request_date'])) {
                // DATETIME形式の場合はDATE部分のみを抽出
                if (strpos($data['request_date'], ' ') !== false) {
                    $data['request_date'] = explode(' ', $data['request_date'])[0];
                }
            }
            
            // 時刻形式の正規化（TIME型用）
            if (isset($data['start_time'])) {
                // DATETIME形式の場合はTIME部分のみを抽出
                if (strpos($data['start_time'], ' ') !== false) {
                    $data['start_time'] = explode(' ', $data['start_time'])[1] ?? $data['start_time'];
                }
                // 日付部分が含まれている場合は時刻部分のみを抽出
                if (strpos($data['start_time'], 'T') !== false) {
                    $data['start_time'] = explode('T', $data['start_time'])[1] ?? $data['start_time'];
                }
            }
            
            if (isset($data['end_time'])) {
                // DATETIME形式の場合はTIME部分のみを抽出
                if (strpos($data['end_time'], ' ') !== false) {
                    $data['end_time'] = explode(' ', $data['end_time'])[1] ?? $data['end_time'];
                }
                // 日付部分が含まれている場合は時刻部分のみを抽出
                if (strpos($data['end_time'], 'T') !== false) {
                    $data['end_time'] = explode('T', $data['end_time'])[1] ?? $data['end_time'];
                }
                // 秒部分がある場合は除去（HH:MM:SS → HH:MM）
                if (substr_count($data['end_time'], ':') === 2) {
                    $parts = explode(':', $data['end_time']);
                    $data['end_time'] = $parts[0] . ':' . $parts[1];
                }
            }
            
            // request_timeの正規化
            if (isset($data['request_time'])) {
                // DATETIME形式の場合はTIME部分のみを抽出
                if (strpos($data['request_time'], ' ') !== false) {
                    $data['request_time'] = explode(' ', $data['request_time'])[1] ?? $data['request_time'];
                }
                // ISO形式（T区切り）の場合は時刻部分のみを抽出
                if (strpos($data['request_time'], 'T') !== false) {
                    $data['request_time'] = explode('T', $data['request_time'])[1] ?? $data['request_time'];
                }
                // 秒部分がある場合は除去（HH:MM:SS → HH:MM）
                if (substr_count($data['request_time'], ':') === 2) {
                    $parts = explode(':', $data['request_time']);
                    $data['request_time'] = $parts[0] . ':' . $parts[1];
                }
            } else {
                $data['request_time'] = $data['start_time']; // 後方互換性
            }
            
            // start_timeの秒部分も除去
            if (isset($data['start_time']) && substr_count($data['start_time'], ':') === 2) {
                $parts = explode(':', $data['start_time']);
                $data['start_time'] = $parts[0] . ':' . $parts[1];
            }
            
            // 繰り返し設定の処理
            $repeatEnabled = ($request->repeat_enabled ?? '0') === '1';
            $createdRequests = [];
            
            if ($repeatEnabled) {
                // 繰り返し依頼：バッチで一括作成を試行し、0件のときだけ1件ずつフォールバック
                $repeatDates = $this->generateRepeatDates($request);
                
                \Log::info('繰り返し依頼生成', [
                    'user_id' => Auth::id(),
                    'dates_count' => count($repeatDates),
                    'dates' => $repeatDates,
                ]);
                
                $batchResult = $this->requestService->createRequestsBatch($data, $repeatDates, Auth::id());
                $createdRequests = $batchResult['created'];
                if (count($createdRequests) === 0 && count($repeatDates) > 0) {
                    \Log::warning('createRequestsBatch が0件のため、1件ずつ作成にフォールバック', ['user_id' => Auth::id()]);
                    foreach ($repeatDates as $date) {
                        $requestData = $data;
                        $requestData['request_date'] = $date;
                        $createdRequest = $this->requestService->createRequest($requestData, Auth::id());
                        $createdRequests[] = $createdRequest;
                    }
                }
                $successMessage = count($createdRequests) . '件の依頼が作成されました';
                if (!empty($batchResult['skipped_message'])) {
                    $successMessage .= '（' . $batchResult['skipped_message'] . '）';
                }
            } else {
                // 単一依頼を作成
                $createdRequest = $this->requestService->createRequest($data, Auth::id());
                $createdRequests[] = $createdRequest;
                
                $successMessage = $createdRequest->formatted_notes !== null
                    ? '依頼が作成されました（音声入力をAIで整形しました）'
                    : '依頼が作成されました';
            }

            \Log::info('依頼作成完了', [
                'user_id' => Auth::id(),
                'request_ids' => array_map(fn($r) => $r->id, $createdRequests),
                'count' => count($createdRequests),
            ]);

            return redirect()->route('requests.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Log::error('依頼作成エラー: ' . $e->getMessage(), [
                'data' => $data ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 繰り返し設定から日付の配列を生成
     */
    private function generateRepeatDates(Request $request): array
    {
        $dates = [];
        $baseDate = $request->request_date;
        $repeatType = $request->repeat_type ?? 'weekly';
        $today = Carbon::today();
        
        if (!$baseDate) {
            return [];
        }
        
        // 基準日を追加（過去でなければ）
        $baseDateCarbon = Carbon::parse($baseDate);
        if ($baseDateCarbon->gte($today)) {
            $dates[] = $baseDate;
        }
        
        if ($repeatType === 'weekly') {
            // 毎週パターン
            $weekdays = json_decode($request->repeat_weekdays ?? '[]', true) ?: [];
            $interval = (int)($request->repeat_interval ?? 1);
            $until = $request->repeat_until ? Carbon::parse($request->repeat_until) : null;
            $maxWeeks = 12;
            
            if (empty($weekdays)) {
                // 曜日未指定の場合、基準日の曜日を使用
                $weekdays = [$baseDateCarbon->dayOfWeek];
            }
            
            // 最大終了日（12週間後）
            $maxEndDate = $baseDateCarbon->copy()->addWeeks($maxWeeks);
            if ($until && $until->lt($maxEndDate)) {
                $maxEndDate = $until;
            }
            
            for ($week = 0; $week < $maxWeeks; $week++) {
                foreach ($weekdays as $dayOfWeek) {
                    // 基準日の週の開始日から計算
                    $weekStart = $baseDateCarbon->copy()->startOfWeek(Carbon::SUNDAY);
                    $date = $weekStart->copy()
                        ->addWeeks($week * $interval)
                        ->addDays($dayOfWeek);
                    
                    // 過去の日付はスキップ
                    if ($date->lt($today)) {
                        continue;
                    }
                    
                    // 終了日を超えたらスキップ
                    if ($date->gt($maxEndDate)) {
                        continue;
                    }
                    
                    $dateStr = $date->format('Y-m-d');
                    if (!in_array($dateStr, $dates)) {
                        $dates[] = $dateStr;
                    }
                }
            }
        } elseif ($repeatType === 'monthly') {
            // 毎月パターン
            $monthCount = (int)($request->repeat_month_count ?? 3);
            $dayOfMonth = $baseDateCarbon->day;
            
            for ($i = 1; $i < $monthCount; $i++) {
                $date = $baseDateCarbon->copy()->addMonths($i);
                
                // 月末調整（例：1/31の翌月は2/28に）
                $lastDayOfMonth = $date->copy()->endOfMonth()->day;
                if ($dayOfMonth > $lastDayOfMonth) {
                    $date->day = $lastDayOfMonth;
                } else {
                    $date->day = $dayOfMonth;
                }
                
                // 過去の日付はスキップ
                if ($date->lt($today)) {
                    continue;
                }
                
                $dateStr = $date->format('Y-m-d');
                if (!in_array($dateStr, $dates)) {
                    $dates[] = $dateStr;
                }
            }
        } elseif ($repeatType === 'custom') {
            // カスタム日付
            $customDates = json_decode($request->repeat_custom_dates ?? '[]', true) ?: [];
            
            foreach ($customDates as $customDate) {
                if (!$customDate) {
                    continue;
                }
                
                $date = Carbon::parse($customDate);
                
                // 過去の日付はスキップ
                if ($date->lt($today)) {
                    continue;
                }
                
                $dateStr = $date->format('Y-m-d');
                if (!in_array($dateStr, $dates)) {
                    $dates[] = $dateStr;
                }
            }
        }
        
        // 日付順にソート
        sort($dates);
        
        return $dates;
    }
}

