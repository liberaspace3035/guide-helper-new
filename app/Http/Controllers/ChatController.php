<?php

namespace App\Http\Controllers;

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

    public function show($matchingId)
    {
        try {
            $user = Auth::user();
            
            $messages = $this->chatService->getMessages($matchingId, Auth::id());
            return view('chat.show', [
                'matchingId' => $matchingId,
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }
}

