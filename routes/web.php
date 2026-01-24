<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// 認証ルート
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// 認証が必要なルート
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
           // ユーザー専用ルート
           Route::middleware(['role:user'])->group(function () {
               Route::get('/requests/new', [\App\Http\Controllers\RequestController::class, 'create'])->name('requests.create');
               Route::post('/requests', [\App\Http\Controllers\RequestController::class, 'store'])->name('requests.store');
               Route::get('/requests', [\App\Http\Controllers\RequestController::class, 'index'])->name('requests.index');
               Route::get('/reports/{id}', [\App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
               Route::post('/reports/{id}/approve', [\App\Http\Controllers\ReportController::class, 'approve'])->name('reports.approve');
               Route::post('/reports/{id}/request-revision', [\App\Http\Controllers\ReportController::class, 'requestRevision'])->name('reports.request-revision');
           });
           
           // ガイド専用ルート
           Route::middleware(['role:guide'])->group(function () {
               Route::get('/guide/requests', [\App\Http\Controllers\Guide\RequestController::class, 'index'])->name('guide.requests.index');
               Route::get('/guide/reports/new/{matchingId}', [\App\Http\Controllers\Guide\ReportController::class, 'create'])->name('guide.reports.create');
               Route::post('/guide/reports', [\App\Http\Controllers\Guide\ReportController::class, 'store'])->name('guide.reports.store');
               Route::post('/guide/reports/{id}/submit', [\App\Http\Controllers\Guide\ReportController::class, 'submit'])->name('guide.reports.submit');
           });
    
           // 共通ルート
           Route::get('/matchings/{id}', [\App\Http\Controllers\MatchingController::class, 'show'])->name('matchings.show');
           Route::get('/chat/{matchingId}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
           Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');
           Route::post('/announcements/{id}/read', [\App\Http\Controllers\AnnouncementController::class, 'markAsRead'])->name('announcements.read');
    
    // 管理者専用ルート
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    });
    
    // ユーザー自身の月次限度時間関連（セッション認証用）
    Route::get('/api/users/me/monthly-limit', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimit']);
    Route::get('/api/users/me/monthly-limits', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimits']);

    // マッチング関連（セッション認証用）
    Route::post('/api/matchings/accept', [\App\Http\Controllers\Api\MatchingController::class, 'accept']);
    Route::post('/api/matchings/decline', [\App\Http\Controllers\Api\MatchingController::class, 'decline']);

    // 依頼関連（セッション認証用）
    Route::get('/api/requests/guide/available', [\App\Http\Controllers\Api\RequestController::class, 'availableForGuide']);
});

