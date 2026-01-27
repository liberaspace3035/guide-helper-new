<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ChatService;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'matching_id' => 'required|integer|exists:matchings,id',
            'message' => 'required|string|max:1000',
        ]);

        try {
            // セッション認証を使用
            $senderId = auth()->id();
            
            if (!$senderId) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            \Log::info('ChatController::sendMessage', [
                'auth_id' => $senderId,
                'user' => auth()->user(),
                'matching_id' => $request->input('matching_id'),
                'message' => $request->input('message')
            ]);
            $chatMessage = $this->chatService->sendMessage(
                $request->input('matching_id'),
                $request->input('message'),
                $senderId
            );

            return response()->json([
                'message' => 'メッセージが送信されました',
                'chat_message' => [
                    'id' => $chatMessage->id,
                    'matching_id' => $chatMessage->matching_id,
                    'sender_id' => $chatMessage->sender_id,
                    'message' => $chatMessage->message,
                    'created_at' => $chatMessage->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function getMessages($matchingId)
    {
        try {
            // セッション認証を使用
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json(['error' => '認証が必要です'], 401);
            }
            
            $messages = $this->chatService->getMessages($matchingId, $userId);
            
            return response()->json(['messages' => $messages]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function unreadCount()
    {
        // セッション認証を使用
        $userId = auth()->id();
        
        if (!$userId) {
            return response()->json(['error' => '認証が必要です'], 401);
        }
        
        $count = $this->chatService->getUnreadCount($userId);
        
        return response()->json(['unread_count' => $count]);
    }
}


