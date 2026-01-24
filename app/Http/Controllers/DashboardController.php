<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = Auth::user();
        $dashboardData = $this->dashboardService->getDashboardData($user);
        
        return view('dashboard', array_merge([
            'user' => $user,
        ], $dashboardData));
    }
}
