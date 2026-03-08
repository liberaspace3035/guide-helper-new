<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ReportService;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function show($id)
    {
        try {
            $report = $this->reportService->getUserReport($id, Auth::id());
            return view('reports.show', ['report' => $report]);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        // 評価のバリデーション
        $request->validate([
            'guide_rating' => 'required|integer|in:1,2,3',
            'guide_rating_comment' => 'required|string|max:1000',
        ], [
            'guide_rating.required' => '評価を選択してください。',
            'guide_rating.in' => '評価は1〜3の値で選択してください。',
            'guide_rating_comment.required' => '評価コメントを入力してください。',
            'guide_rating_comment.max' => '評価コメントは1000文字以内で入力してください。',
        ]);

        try {
            // 評価を保存
            $this->reportService->saveUserRating(
                $id,
                Auth::id(),
                (int) $request->input('guide_rating'),
                $request->input('guide_rating_comment')
            );

            // 報告書を承認
            $this->reportService->approveReport($id, Auth::id());
            
            // AJAXリクエストの場合はJSONを返す
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => '報告書を承認しました'
                ]);
            }
            
            return redirect()->route('dashboard')
                ->with('success', '報告書を承認しました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function requestRevision(Request $request, $id)
    {
        $request->validate([
            'revision_notes' => 'required|string|max:1000',
        ]);

        try {
            $this->reportService->requestRevision($id, Auth::id(), $request->input('revision_notes'));
            return redirect()->route('dashboard')
                ->with('success', '修正依頼を送信しました');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
