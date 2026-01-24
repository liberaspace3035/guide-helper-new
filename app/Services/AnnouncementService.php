<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
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
}






