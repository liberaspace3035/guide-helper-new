<?php

namespace App\Http\Controllers;

use App\Services\SupportMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportMessageController extends Controller
{
    public function __construct(
        protected SupportMessageService $supportMessageService
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user->isUser() && !$user->isGuide()) {
            abort(403);
        }

        $messages = $user->supportMessages()->orderBy('created_at')->get();

        return view('support.index', [
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isUser() && !$user->isGuide()) {
            abort(403);
        }

        $data = $request->validate([
            'body' => 'required|string|min:1|max:10000',
        ]);

        $this->supportMessageService->postFromParticipant($user, $data['body']);

        return redirect()->route('support.index')->with('success', '送信しました。運営宛に通知し、登録メールアドレスへ自動返信を送りました。');
    }
}
