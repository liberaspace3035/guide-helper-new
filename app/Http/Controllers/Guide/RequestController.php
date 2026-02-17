<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\RequestService;

class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
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
        return view('guide.requests.index', [
            'requests' => $requests,
        ]);
    }
}

