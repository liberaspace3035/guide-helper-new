<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 認証ルート
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'apiLogout']);
    
    // 依頼関連
    Route::get('/requests/my-requests', [RequestController::class, 'myRequests']);
    Route::get('/requests/guide/available', [RequestController::class, 'availableForGuide']);
    Route::get('/requests/{id}/applicants', [RequestController::class, 'applicants']);
    Route::get('/requests/matched-guides/all', [RequestController::class, 'matchedGuides']);
    Route::post('/requests/{id}/select-guide', [RequestController::class, 'selectGuide']);
    Route::get('/guides/available', [RequestController::class, 'availableGuides']); // 指名用ガイド一覧
    
    // マッチング関連
    Route::post('/matchings/accept', [MatchingController::class, 'accept']);
    Route::post('/matchings/decline', [MatchingController::class, 'decline']);
    Route::get('/matchings/my-matchings', [MatchingController::class, 'myMatchings']);
    Route::post('/matchings/{id}/cancel', [MatchingController::class, 'cancel']);
    
    // チャット関連
    Route::post('/chat/messages', [ChatController::class, 'sendMessage']);
    Route::get('/chat/messages/{matchingId}', [ChatController::class, 'getMessages']);
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount']);
    
    // お知らせ関連
    Route::get('/announcements', [\App\Http\Controllers\AnnouncementController::class, 'index']);
    Route::post('/announcements/{id}/read', [\App\Http\Controllers\AnnouncementController::class, 'markAsRead']);
    
    // ユーザー自身の月次限度時間関連
    Route::get('/users/me/monthly-limit', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimit']);
    Route::get('/users/me/monthly-limits', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimits']);
    
    // ユーザー統計（管理者のみ）
    Route::get('/users/stats', [\App\Http\Controllers\Api\AdminController::class, 'userStats'])->middleware('role:admin');
    
    // 管理者関連
    Route::middleware(['role:admin', 'throttle:admin'])->prefix('admin')->group(function () {
        Route::get('/requests', [\App\Http\Controllers\Api\AdminController::class, 'requests']);
        Route::get('/operation-logs', [\App\Http\Controllers\Api\AdminController::class, 'operationLogs']);
        Route::get('/email-templates', [\App\Http\Controllers\Api\EmailTemplateController::class, 'index']);
        Route::put('/email-templates/{id}', [\App\Http\Controllers\Api\EmailTemplateController::class, 'update']);
        Route::get('/email-settings', [\App\Http\Controllers\Api\EmailTemplateController::class, 'settings']);
        Route::put('/email-settings/{id}', [\App\Http\Controllers\Api\EmailTemplateController::class, 'updateSetting']);
        Route::get('/acceptances', [\App\Http\Controllers\Api\AdminController::class, 'acceptances']);
        Route::get('/reports', [\App\Http\Controllers\Api\AdminController::class, 'reports']);
        Route::get('/reports/user-approved', [\App\Http\Controllers\Api\AdminController::class, 'userApprovedReports']);
        Route::post('/reports/{id}/approve', [\App\Http\Controllers\Api\AdminController::class, 'approveReport']);
        Route::get('/reports/csv', [\App\Http\Controllers\Api\AdminController::class, 'reportsCsv']);
        Route::get('/reports/{id}/csv', [\App\Http\Controllers\Api\AdminController::class, 'reportCsv']);
        Route::get('/usage/csv', [\App\Http\Controllers\Api\AdminController::class, 'usageCsv']);
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/settings/auto-matching', [\App\Http\Controllers\Api\AdminController::class, 'getAutoMatching']);
        Route::put('/settings/auto-matching', [\App\Http\Controllers\Api\AdminController::class, 'updateAutoMatching']);
        Route::post('/matchings/approve', [\App\Http\Controllers\Api\AdminController::class, 'approveMatching']);
        Route::post('/matchings/reject', [\App\Http\Controllers\Api\AdminController::class, 'rejectMatching']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::get('/guides', [\App\Http\Controllers\Api\AdminController::class, 'guides']);
            Route::put('/users/{id}/profile-extra', [\App\Http\Controllers\Api\AdminController::class, 'updateUserProfileExtra']);
            Route::put('/guides/{id}/profile-extra', [\App\Http\Controllers\Api\AdminController::class, 'updateGuideProfileExtra']);
        Route::put('/users/{id}/approve', [\App\Http\Controllers\Api\AdminController::class, 'approveUser']);
        Route::put('/users/{id}/reject', [\App\Http\Controllers\Api\AdminController::class, 'rejectUser']);
        Route::put('/users/{id}/monthly-limit', [\App\Http\Controllers\Api\AdminController::class, 'setUserMonthlyLimit']);
        Route::get('/users/{id}/monthly-limits', [\App\Http\Controllers\Api\AdminController::class, 'getUserMonthlyLimits']);
        Route::put('/guides/{id}/approve', [\App\Http\Controllers\Api\AdminController::class, 'approveGuide']);
        Route::put('/guides/{id}/reject', [\App\Http\Controllers\Api\AdminController::class, 'rejectGuide']);
    });

    // 管理者向けお知らせ管理
    Route::middleware(['role:admin'])->prefix('announcements/admin')->group(function () {
        Route::get('/all', [\App\Http\Controllers\AnnouncementController::class, 'getAllForAdmin']);
        Route::post('/', [\App\Http\Controllers\AnnouncementController::class, 'createForAdmin']);
        Route::put('/{id}', [\App\Http\Controllers\AnnouncementController::class, 'updateForAdmin']);
        Route::delete('/{id}', [\App\Http\Controllers\AnnouncementController::class, 'deleteForAdmin']);
    });
});

