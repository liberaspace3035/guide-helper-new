<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GuideProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuideProposalController extends Controller
{
    public function __construct(
        protected GuideProposalService $proposalService
    ) {}

    /**
     * ガイド: 自分が送った提案一覧
     */
    public function indexForGuide()
    {
        $user = Auth::user();
        if ($user->role !== 'guide') {
            return response()->json(['error' => 'ガイドのみ利用できます'], 403);
        }
        $list = $this->proposalService->listForGuide($user->id);
        return response()->json(['proposals' => $list]);
    }

    /**
     * ガイド: 提案先にできる利用者一覧
     */
    public function usersForProposal()
    {
        $user = Auth::user();
        if ($user->role !== 'guide') {
            return response()->json(['error' => 'ガイドのみ利用できます'], 403);
        }
        $users = $this->proposalService->listUsersForProposal($user->id);
        return response()->json(['users' => $users]);
    }

    /**
     * ガイド: 支援を提案する
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'guide') {
            return response()->json(['error' => 'ガイドのみ利用できます'], 403);
        }
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'request_type' => 'required|in:outing,home',
            'proposed_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'service_content' => 'nullable|string|max:2000',
            'message' => 'nullable|string|max:1000',
            'prefecture' => 'nullable|string|max:10',
            'destination_address' => 'nullable|string|max:500',
            'meeting_place' => 'nullable|string|max:500',
        ]);
        try {
            $proposal = $this->proposalService->create($user->id, $request->all());
            return response()->json(['proposal' => $proposal, 'message' => '提案を送信しました']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * 利用者: 自分宛の提案一覧（未対応のみ）
     */
    public function indexForUser(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'user') {
            return response()->json(['error' => '利用者のみ利用できます'], 403);
        }
        $pendingOnly = $request->boolean('pending_only', true);
        $list = $this->proposalService->listForUser($user->id, $pendingOnly);
        return response()->json(['proposals' => $list]);
    }

    /**
     * 利用者: 提案を承諾する
     */
    public function accept(int $id)
    {
        $user = Auth::user();
        if ($user->role !== 'user') {
            return response()->json(['error' => '利用者のみ利用できます'], 403);
        }
        try {
            $result = $this->proposalService->accept($id, $user->id);
            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => '提案が見つかりません'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * 利用者: 提案を拒否する
     */
    public function reject(int $id)
    {
        $user = Auth::user();
        if ($user->role !== 'user') {
            return response()->json(['error' => '利用者のみ利用できます'], 403);
        }
        try {
            $this->proposalService->reject($id, $user->id);
            return response()->json(['message' => '提案を辞退しました']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => '提案が見つかりません'], 404);
        }
    }
}
