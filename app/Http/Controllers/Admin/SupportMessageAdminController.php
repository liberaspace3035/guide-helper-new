<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\SupportMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SupportMessageAdminController extends Controller
{
    public function __construct(
        protected SupportMessageService $supportMessageService
    ) {}

    public function index()
    {
        $supportTableReady = Schema::hasTable('support_messages');
        $threads = collect();
        $supportSetupWarning = null;

        if ($supportTableReady) {
            $participantIds = SupportMessage::query()->distinct()->pluck('user_id');
            $participants = User::query()
                ->whereIn('id', $participantIds)
                ->orderBy('name')
                ->get();

            $threads = $participants->map(function (User $u) {
                $last = SupportMessage::query()->where('user_id', $u->id)->orderByDesc('id')->first();

                return [
                    'user' => $u,
                    'last_message' => $last,
                ];
            })->sortByDesc(function ($t) {
                return $t['last_message']?->created_at?->getTimestamp() ?? 0;
            })->values();
        } else {
            $supportSetupWarning = 'サポートメッセージ機能のテーブルが未作成です。`php artisan migrate --force` を実行してください。';
        }

        $allTargets = User::query()
            ->whereIn('role', ['user', 'guide'])
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.support.index', [
            'threads' => $threads,
            'allTargets' => $allTargets,
            'autoReplySubject' => $this->supportMessageService->getAutoReplySubject(),
            'autoReplyBody' => $this->supportMessageService->getAutoReplyBodyTemplate(),
            'supportSetupWarning' => $supportSetupWarning,
        ]);
    }

    public function show(int $userId)
    {
        $participant = User::query()
            ->whereIn('role', ['user', 'guide'])
            ->findOrFail($userId);

        $messages = SupportMessage::query()
            ->where('user_id', $participant->id)
            ->orderBy('created_at')
            ->get();

        return view('admin.support.thread', [
            'participant' => $participant,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, int $userId)
    {
        $admin = Auth::user();
        $data = $request->validate([
            'body' => 'required|string|min:1|max:10000',
        ]);

        $this->supportMessageService->postFromAdmin($admin, $userId, $data['body']);

        return redirect()->route('admin.support.show', $userId)->with('success', '送信しました。相手の通知欄にも表示されます。');
    }

    public function storeNew(Request $request)
    {
        $admin = Auth::user();
        $data = $request->validate([
            'target_user_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|min:1|max:10000',
        ]);

        $target = User::query()->whereIn('role', ['user', 'guide'])->findOrFail($data['target_user_id']);
        $this->supportMessageService->postFromAdmin($admin, $target->id, $data['body']);

        return redirect()->route('admin.support.show', $target->id)->with('success', '送信しました。相手の通知欄にも表示されます。');
    }

    public function updateAutoReply(Request $request)
    {
        $data = $request->validate([
            'support_auto_reply_subject' => 'required|string|max:255',
            'support_auto_reply_body' => 'required|string|max:20000',
        ]);

        $this->supportMessageService->saveAutoReplyTemplate(
            $data['support_auto_reply_subject'],
            $data['support_auto_reply_body']
        );

        return redirect()->route('admin.support.index')->with('success', '自動返信の定型文を保存しました。');
    }
}
