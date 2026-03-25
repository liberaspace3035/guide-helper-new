<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventCalendarService;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function __construct(private EventCalendarService $eventService)
    {
    }

    public function show(int $id)
    {
        $event = Event::findOrFail($id);
        $user = Auth::user();
        if (!$this->eventService->canViewForPrefill($user, $event)) {
            return response()->json(['error' => 'イベントが見つかりません'], 404);
        }

        return response()->json(['event' => $this->eventService->eventToPublicArray($event)]);
    }
}
