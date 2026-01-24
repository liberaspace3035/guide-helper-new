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
        $requests = $this->requestService->getAvailableRequestsForGuide($guide->id);
        
        return view('guide.requests.index', [
            'requests' => $requests,
        ]);
    }
}

