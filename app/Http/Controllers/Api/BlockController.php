<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockController extends Controller
{
    protected BlockService $blockService;

    public function __construct(BlockService $blockService)
    {
        $this->blockService = $blockService;
    }

    public function block(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $block = $this->blockService->block(
                Auth::id(),
                $request->input('user_id'),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'ブロックしました。',
                'block_id' => $block->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function unblock(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->blockService->unblock(
                Auth::id(),
                $request->input('user_id')
            );

            return response()->json([
                'success' => true,
                'message' => 'ブロックを解除しました。',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function myBlocks()
    {
        $blocks = $this->blockService->getBlockedList(Auth::id());

        return response()->json([
            'success' => true,
            'blocks' => $blocks,
        ]);
    }

    public function adminGetAllBlocks()
    {
        $blocks = $this->blockService->getAllBlocks();

        return response()->json([
            'success' => true,
            'blocks' => $blocks,
        ]);
    }

    public function adminBlock(Request $request)
    {
        $request->validate([
            'blocker_id' => 'required|integer|exists:users,id',
            'blocked_id' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $block = $this->blockService->adminBlock(
                $request->input('blocker_id'),
                $request->input('blocked_id'),
                $request->input('reason'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => '管理者としてブロックを設定しました。',
                'block_id' => $block->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function adminUnblock(Request $request, int $id)
    {
        try {
            $this->blockService->adminUnblock($id);

            return response()->json([
                'success' => true,
                'message' => 'ブロックを解除しました。',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
