<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AnnouncementService;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    protected $announcementService;

    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    public function index()
    {
        $user = Auth::user();
        $announcements = $this->announcementService->getAllAnnouncements($user->id, $user->role);
        
        return view('announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function markAsRead($id)
    {
        try {
            $this->announcementService->markAsRead($id, Auth::id(), Auth::user()->role);
            return response()->json(['message' => 'お知らせを既読にしました']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // 管理者向け：すべてのお知らせを取得
    public function getAllForAdmin()
    {
        $announcements = Announcement::with('createdByUser:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'target_audience' => $announcement->target_audience,
                    'created_by' => $announcement->created_by,
                    'created_by_name' => $announcement->createdByUser->name ?? '不明',
                    'created_at' => $announcement->created_at,
                ];
            });

        return response()->json(['announcements' => $announcements]);
    }

    // 管理者向け：お知らせを作成
    public function createForAdmin(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:user,guide,all',
        ]);

        try {
            $announcement = $this->announcementService->createAnnouncement(
                $request->only(['title', 'content', 'target_audience']),
                Auth::id()
            );

            $announcement->load('createdByUser:id,name');

            return response()->json([
                'announcement' => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'target_audience' => $announcement->target_audience,
                    'created_by' => $announcement->created_by,
                    'created_by_name' => $announcement->createdByUser->name ?? '不明',
                    'created_at' => $announcement->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // 管理者向け：お知らせを更新
    public function updateForAdmin(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:user,guide,all',
        ]);

        try {
            $announcement = $this->announcementService->updateAnnouncement(
                $id,
                $request->only(['title', 'content', 'target_audience'])
            );

            $announcement->load('createdByUser:id,name');

            return response()->json([
                'announcement' => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'target_audience' => $announcement->target_audience,
                    'created_by' => $announcement->created_by,
                    'created_by_name' => $announcement->createdByUser->name ?? '不明',
                    'created_at' => $announcement->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // 管理者向け：お知らせを削除
    public function deleteForAdmin($id)
    {
        try {
            $this->announcementService->deleteAnnouncement($id);
            return response()->json(['message' => 'お知らせを削除しました']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

