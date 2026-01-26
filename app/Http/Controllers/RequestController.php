<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\RequestService;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
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
        return view('requests.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|in:outing,home',
            'destination_address' => 'required|string',
            'meeting_place' => 'required_if:request_type,outing|nullable|string',
            'service_content' => 'required|string',
            'request_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'guide_gender' => 'nullable|in:none,male,female',
            'guide_age' => 'nullable|in:none,20s,30s,40s,50s,60s',
        ]);

        if ($validator->fails()) {
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
            
            $createdRequest = $this->requestService->createRequest($data, Auth::id());
            
            return redirect()->route('requests.index')
                ->with('success', '依頼が作成されました');
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
}

