<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AnnouncementService
{
    public function getUnreadAnnouncements(int $userId, string $userRole): array
    {
        $query = Announcement::leftJoin('announcement_reads', function($join) use ($userId) {
                $join->on('announcements.id', '=', 'announcement_reads.announcement_id')
                     ->where('announcement_reads.user_id', '=', $userId);
            })
            ->whereNull('announcement_reads.id');

        // ロールに応じたフィルタリング
        if ($userRole === 'user') {
            $query->where(function($q) {
                $q->where('announcements.target_audience', 'user')
                  ->orWhere('announcements.target_audience', 'all');
            });
        } elseif ($userRole === 'guide') {
            $query->where(function($q) {
                $q->where('announcements.target_audience', 'guide')
                  ->orWhere('announcements.target_audience', 'all');
            });
        }
        // adminは全て表示

        return $query->select('announcements.*')
            ->orderBy('announcements.created_at', 'desc')
            ->get()
            ->map(function($announcement) {
                $announcement->is_read = 0;
                return $announcement;
            })
            ->toArray();
    }

    public function getAllAnnouncements(int $userId, string $userRole): array
    {
        $query = Announcement::leftJoin('announcement_reads', function($join) use ($userId) {
                $join->on('announcements.id', '=', 'announcement_reads.announcement_id')
                     ->where('announcement_reads.user_id', '=', $userId);
            });

        // ロールに応じたフィルタリング
        if ($userRole === 'user') {
            $query->where(function($q) {
                $q->where('announcements.target_audience', 'user')
                  ->orWhere('announcements.target_audience', 'all');
            });
        } elseif ($userRole === 'guide') {
            $query->where(function($q) {
                $q->where('announcements.target_audience', 'guide')
                  ->orWhere('announcements.target_audience', 'all');
            });
        }
        // adminは全て表示

        return $query->select('announcements.*', 'announcement_reads.read_at')
            ->selectRaw('CASE WHEN announcement_reads.id IS NOT NULL THEN 1 ELSE 0 END as is_read')
            ->orderBy('announcements.created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function markAsRead(int $announcementId, int $userId, string $userRole): void
    {
        // お知らせが存在し、ユーザーが対象か確認
        $announcement = Announcement::findOrFail($announcementId);

        // 対象者チェック
        if ($userRole === 'user' && $announcement->target_audience === 'guide') {
            throw new \Exception('このお知らせは対象外です');
        }
        if ($userRole === 'guide' && $announcement->target_audience === 'user') {
            throw new \Exception('このお知らせは対象外です');
        }

        // 既読レコードを追加（既に存在する場合は更新）
        AnnouncementRead::updateOrCreate(
            [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * お知らせを未読に戻す（announcement_reads のレコードを削除）
     */
    public function markAsUnread(int $announcementId, int $userId, string $userRole): void
    {
        $announcement = Announcement::findOrFail($announcementId);

        if ($userRole === 'user' && $announcement->target_audience === 'guide') {
            throw new \Exception('このお知らせは対象外です');
        }
        if ($userRole === 'guide' && $announcement->target_audience === 'user') {
            throw new \Exception('このお知らせは対象外です');
        }

        AnnouncementRead::where('announcement_id', $announcementId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function createAnnouncement(array $data, int $createdBy): Announcement
    {
        return Announcement::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'],
            'created_by' => $createdBy,
        ]);
    }

    public function updateAnnouncement(int $announcementId, array $data): Announcement
    {
        $announcement = Announcement::findOrFail($announcementId);
        $announcement->update($data);
        return $announcement;
    }

    public function deleteAnnouncement(int $announcementId): void
    {
        Announcement::findOrFail($announcementId)->delete();
    }

    /**
     * お知らせの既読状況を取得（管理者用）
     * 対象者数・既読数・既読者一覧を返す
     */
    public function getReadStatus(int $announcementId): array
    {
        $announcement = Announcement::findOrFail($announcementId);

        $roleCondition = match ($announcement->target_audience) {
            'user' => ['user'],
            'guide' => ['guide'],
            default => ['user', 'guide'],
        };
        $totalTarget = User::whereIn('role', $roleCondition)->count();

        $reads = AnnouncementRead::where('announcement_id', $announcementId)
            ->with('user:id,name')
            ->orderBy('read_at', 'desc')
            ->get();

        $readers = $reads->map(function ($r) {
            return [
                'user_id' => $r->user_id,
                'name' => $r->user->name ?? '',
                'read_at' => $r->read_at?->toIso8601String(),
            ];
        })->values()->toArray();

        return [
            'announcement_id' => $announcementId,
            'title' => $announcement->title,
            'target_audience' => $announcement->target_audience,
            'total_target' => $totalTarget,
            'read_count' => $reads->count(),
            'readers' => $readers,
        ];
    }
}






