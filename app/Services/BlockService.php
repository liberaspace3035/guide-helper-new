<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Support\Facades\Auth;

class BlockService
{
    public function block(int $blockerId, int $blockedId, ?string $reason = null, ?int $adminId = null): UserBlock
    {
        if ($blockerId === $blockedId) {
            throw new \InvalidArgumentException('自分自身をブロックすることはできません。');
        }

        $blockedUser = User::find($blockedId);
        if (! $blockedUser) {
            throw new \InvalidArgumentException('指定されたユーザーが見つかりません。');
        }

        $existing = UserBlock::where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('既にブロック済みです。');
        }

        return UserBlock::create([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
            'reason' => $reason,
            'blocked_by_admin_id' => $adminId,
        ]);
    }

    public function unblock(int $blockerId, int $blockedId): bool
    {
        $block = UserBlock::where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->first();

        if (! $block) {
            throw new \InvalidArgumentException('ブロックが見つかりません。');
        }

        return $block->delete();
    }

    public function getBlockedList(int $userId): array
    {
        $blocks = UserBlock::with('blocked:id,name,email,role')
            ->where('blocker_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $blocks->map(function ($block) {
            return [
                'id' => $block->id,
                'blocked_user' => [
                    'id' => $block->blocked->id,
                    'name' => $block->blocked->name,
                    'email' => $block->blocked->email,
                    'role' => $block->blocked->role,
                ],
                'reason' => $block->reason,
                'is_admin_block' => $block->isAdminBlock(),
                'created_at' => $block->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    public function getBlockedByList(int $userId): array
    {
        $blocks = UserBlock::with('blocker:id,name,email,role')
            ->where('blocked_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $blocks->map(function ($block) {
            return [
                'id' => $block->id,
                'blocker_user' => [
                    'id' => $block->blocker->id,
                    'name' => $block->blocker->name,
                    'email' => $block->blocker->email,
                    'role' => $block->blocker->role,
                ],
                'reason' => $block->reason,
                'is_admin_block' => $block->isAdminBlock(),
                'created_at' => $block->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    public function getAllBlocks(): array
    {
        $blocks = UserBlock::with(['blocker:id,name,email,role', 'blocked:id,name,email,role', 'blockedByAdmin:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $blocks->map(function ($block) {
            return [
                'id' => $block->id,
                'blocker' => [
                    'id' => $block->blocker->id,
                    'name' => $block->blocker->name,
                    'email' => $block->blocker->email,
                    'role' => $block->blocker->role,
                ],
                'blocked' => [
                    'id' => $block->blocked->id,
                    'name' => $block->blocked->name,
                    'email' => $block->blocked->email,
                    'role' => $block->blocked->role,
                ],
                'reason' => $block->reason,
                'is_admin_block' => $block->isAdminBlock(),
                'blocked_by_admin' => $block->blockedByAdmin ? [
                    'id' => $block->blockedByAdmin->id,
                    'name' => $block->blockedByAdmin->name,
                ] : null,
                'created_at' => $block->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    public function isBlocked(int $userId1, int $userId2): bool
    {
        return UserBlock::where(function ($query) use ($userId1, $userId2) {
            $query->where('blocker_id', $userId1)->where('blocked_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('blocker_id', $userId2)->where('blocked_id', $userId1);
        })->exists();
    }

    public function getBlockedUserIdsForUser(int $userId): array
    {
        $blockedByMe = UserBlock::where('blocker_id', $userId)->pluck('blocked_id')->toArray();
        $blockedMe = UserBlock::where('blocked_id', $userId)->pluck('blocker_id')->toArray();

        return array_unique(array_merge($blockedByMe, $blockedMe));
    }

    public function adminBlock(int $blockerId, int $blockedId, ?string $reason, int $adminId): UserBlock
    {
        return $this->block($blockerId, $blockedId, $reason, $adminId);
    }

    public function adminUnblock(int $blockId): bool
    {
        $block = UserBlock::find($blockId);
        if (! $block) {
            throw new \InvalidArgumentException('ブロックが見つかりません。');
        }

        return $block->delete();
    }
}
