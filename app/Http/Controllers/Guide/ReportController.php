<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ReportService;
use App\Services\MatchingService;

class ReportController extends Controller
{
    protected $reportService;
    protected $matchingService;

    public function __construct(ReportService $reportService, MatchingService $matchingService)
    {
        $this->reportService = $reportService;
        $this->matchingService = $matchingService;
    }

    public function create($matchingId)
    {
        $guide = Auth::user();
        $matchings = $this->matchingService->getUserMatchings($guide->id);
        $matchingIdInt = (int) $matchingId;
        $matching = collect($matchings)->firstWhere('id', $matchingIdInt);
        
        if (!$matching) {
            \Log::warning('ReportController::create - マッチングが見つかりません', [
                'matching_id' => $matchingIdInt,
                'guide_id' => $guide->id,
                'available_matchings' => collect($matchings)->pluck('id')->toArray(),
            ]);
            return redirect()->route('dashboard')
                ->with('error', 'マッチングが見つかりません');
        }

        // 既存の報告書があるか確認
        $existingReport = \App\Models\Report::where('matching_id', $matchingId)->first();
        
        // 既存の報告書がある場合、時刻を適切な形式に変換
        if ($existingReport) {
            // actual_start_timeとactual_end_timeをH:i形式に変換
            if ($existingReport->actual_start_time) {
                $existingReport->actual_start_time_formatted = is_string($existingReport->actual_start_time) 
                    ? substr($existingReport->actual_start_time, 0, 5)
                    : $existingReport->actual_start_time->format('H:i');
            } else {
                $existingReport->actual_start_time_formatted = null;
            }
            
            if ($existingReport->actual_end_time) {
                $existingReport->actual_end_time_formatted = is_string($existingReport->actual_end_time)
                    ? substr($existingReport->actual_end_time, 0, 5)
                    : $existingReport->actual_end_time->format('H:i');
            } else {
                $existingReport->actual_end_time_formatted = null;
            }
            
            // actual_dateをY-m-d形式に変換
            if ($existingReport->actual_date) {
                $existingReport->actual_date_formatted = is_string($existingReport->actual_date)
                    ? $existingReport->actual_date
                    : $existingReport->actual_date->format('Y-m-d');
            } else {
                $existingReport->actual_date_formatted = null;
            }
        }
        
        return view('guide.reports.create', [
            'matchingId' => $matchingId,
            'matching' => $matching,
            'existingReport' => $existingReport,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'matching_id' => 'required|integer|exists:matchings,id',
            'service_content' => 'required|string|min:1',
            'report_content' => 'nullable|string', // 自由入力の欄は任意
            'actual_date' => 'required|date',
            'actual_start_time' => 'required|date_format:H:i',
            'actual_end_time' => 'required|date_format:H:i',
        ], [
            'service_content.required' => 'サービス内容は必須入力です。',
            'service_content.min' => 'サービス内容を入力してください。',
            'actual_date.required' => '実施日は必須です。',
            'actual_start_time.required' => '開始時刻は必須入力です。',
            'actual_end_time.required' => '終了時刻は必須入力です。',
        ]);

        try {
            $report = $this->reportService->createOrUpdateReport($request->all(), Auth::id());
            
            // AJAXリクエストの場合はJSONを返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => '報告書が保存されました',
                    'report_id' => $report->id
                ]);
            }
            
            return redirect()->route('dashboard')
                ->with('success', '報告書が保存されました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // バリデーションエラーの場合は422を返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // AJAXリクエストの場合はJSONを返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
            
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function submit(Request $request, $id)
    {
        try {
            $this->reportService->submitReport($id, Auth::id());
            
            // AJAXリクエストの場合はJSONを返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => '報告書が提出されました'
                ]);
            }
            
            return redirect()->route('dashboard')
                ->with('success', '報告書が提出されました');
        } catch (\Exception $e) {
            // AJAXリクエストの場合はJSONを返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
            
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
