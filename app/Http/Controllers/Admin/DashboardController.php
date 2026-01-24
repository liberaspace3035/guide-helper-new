<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AdminService;

class DashboardController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // セッション認証を使用（JWTトークンは不要）
        $dashboardData = $this->adminService->getDashboardData();
        
        return view('admin.dashboard', $dashboardData);
    }
}
