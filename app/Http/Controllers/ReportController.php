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

    public function approve($id)
    {
        try {
            $this->reportService->approveReport($id, Auth::id());
            return redirect()->route('dashboard')
                ->with('success', '報告書を承認しました');
        } catch (\Exception $e) {
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
