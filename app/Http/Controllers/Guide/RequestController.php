<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Services\RequestService;
use App\Services\EventCalendarService;

class RequestController extends Controller
{
    protected $requestService;
    protected EventCalendarService $eventCalendarService;

    public function __construct(RequestService $requestService, EventCalendarService $eventCalendarService)
    {
        $this->requestService = $requestService;
        $this->eventCalendarService = $eventCalendarService;
    }

    public function index()
    {
        $guide = Auth::user();
        $guide->load('guideProfile');
        if (!$guide->guideProfile || trim((string) ($guide->guideProfile->introduction ?? '')) === '') {
            return redirect()->route('profile')
                ->withErrors(['error' => '依頼に応募するには、プロフィールの自己PR（自己紹介）の入力が必要です。下記の「自己PR（自己紹介）」欄に入力してください。']);
        }
        $requests = $this->requestService->getAvailableRequestsForGuide($guide->id);

        $prefillEvent = null;
        if (request()->filled('event_id')) {
            $event = Event::find((int) request('event_id'));
            if ($event && $this->eventCalendarService->canViewForPrefill($guide, $event)) {
                $prefillEvent = $this->eventCalendarService->toPrefillForProposal($event);
            }
        }

        return view('guide.requests.index', [
            'requests' => $requests,
            'prefillEvent' => $prefillEvent,
        ]);
    }
}

