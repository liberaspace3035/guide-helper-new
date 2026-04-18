<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PersonalCalendarController;
use App\Http\Controllers\Admin\EventAdminController;
use App\Http\Controllers\Admin\SitePublicNoticeAdminController;
use App\Http\Controllers\Admin\SupportMessageAdminController;
use App\Http\Controllers\SitePublicNoticeController;
use App\Http\Controllers\SupportMessageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/public-notices', [SitePublicNoticeController::class, 'index'])->name('public-notices.index');

// 公開イベントカレンダー（未登録者も閲覧可）
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/new', [EventController::class, 'create'])->name('events.create');
Route::post('/events', [EventController::class, 'store'])->name('events.store');
Route::get('/events/verify', [EventController::class, 'verify'])->name('events.verify');
Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

// キャッシュクリア（Railway 等で環境変数変更後に設定を反映させる用・本番では削除またはアクセス制限を推奨）
Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return 'Cache Cleared（config と cache をクリアしました）。環境変数を変更した場合はこのURLにアクセスしてから再度テストしてください。';
});

// メール設定確認用テスト送信（本番では削除またはアクセス制限を推奨）
Route::get('/test-mail', function () {
    set_time_limit(25); // 応答が返らないままハングしないよう 25 秒で打ち切り
    $to = env('MAIL_TEST_TO', config('mail.from.address'));
    if (empty($to) || $to === 'hello@example.com') {
        return '送信先を設定してください。.env に MAIL_TEST_TO=あなたのメールアドレス を追加するか、MAIL_FROM_ADDRESS を設定してください。';
    }
    try {
        Mail::raw('「One Step」からのテストメールです。設定は正しく動作しています。', function ($message) use ($to) {
            $message->to($to)
                    ->subject('テストメール（One Step）');
        });
        $driver = config('mail.default');
        $msg = 'メール送信完了！受信トレイ（または迷惑メール）を確認してください。送信先: ' . $to . "\n\n現在のメールドライバー: " . $driver;
        if ($driver === 'log') {
            $msg .= "\n\n⚠️ log ドライバーのため、実際にはメールは送信されていません。storage/logs に出力されているだけです。届かない場合は Railway の Variables で MAIL_MAILER=resend（または smtp）に変更し、/clear にアクセスしてから再試行してください。";
        }
        return $msg;
    } catch (\Throwable $e) {
        return '送信失敗: ' . $e->getMessage() . "\n\n環境変数・MAIL_FROM_ADDRESS（Resendの場合は onboarding@resend.dev または認証済みドメイン）を確認し、/clear でキャッシュクリアしてから再試行してください。";
    }
});

// メールデバッグ（送信試行し、失敗時は例外メッセージを表示・本番では削除またはアクセス制限を推奨）
Route::get('/mail-debug', function () {
    set_time_limit(25); // 応答が返らないままハングしないよう 25 秒で打ち切り
    $to = env('MAIL_TEST_TO', config('mail.from.address'));
    if (empty($to) || $to === 'hello@example.com') {
        return '送信先を設定してください。MAIL_TEST_TO または MAIL_FROM_ADDRESS を設定してください。';
    }
    try {
        Mail::raw('Railway Debug - メール設定のテスト送信です。', function ($message) use ($to) {
            $message->to($to)->subject('Railway Debug');
        });
        $driver = config('mail.default');
        $msg = '送信処理は成功しました。受信トレイを確認してください。送信先: ' . $to . "\n\n現在のメールドライバー: " . $driver;
        if ($driver === 'log') {
            $msg .= "\n\n⚠️ log ドライバーのため、実際にはメールは送信されていません。届かない場合は MAIL_MAILER=resend（または smtp）に変更し、/clear でキャッシュクリアしてから再試行してください。";
        }
        return $msg;
    } catch (\Throwable $e) {
        return '送信失敗: ' . $e->getMessage() . "\n\n例外: " . get_class($e);
    }
});

// 認証ルート
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// パスワードリセットルート
Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');

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
               Route::get('/guide/availability', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'index'])->name('guide.availability.index');
               Route::get('/guide/availability/new', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'create'])->name('guide.availability.create');
               Route::post('/guide/availability', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'store'])->name('guide.availability.store');
               Route::get('/guide/availability/{id}/edit', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'edit'])->name('guide.availability.edit');
               Route::put('/guide/availability/{id}', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'update'])->name('guide.availability.update');
               Route::delete('/guide/availability/{id}', [\App\Http\Controllers\Guide\GuideAvailabilityController::class, 'destroy'])->name('guide.availability.destroy');
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
           Route::post('/announcements/{id}/unread', [\App\Http\Controllers\AnnouncementController::class, 'markAsUnread'])->name('announcements.unread');

           Route::get('/support', [SupportMessageController::class, 'index'])->name('support.index');
           Route::post('/support', [SupportMessageController::class, 'store'])->name('support.store')->middleware('throttle:30,1');
    
    // 管理者専用ルート
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/events', [EventAdminController::class, 'index'])->name('admin.events.index');
        Route::get('/admin/events/{id}/edit', [EventAdminController::class, 'edit'])->name('admin.events.edit');
        Route::put('/admin/events/{id}', [EventAdminController::class, 'update'])->name('admin.events.update');
        Route::delete('/admin/events/{id}', [EventAdminController::class, 'destroy'])->name('admin.events.destroy');
        Route::post('/admin/events/bulk-destroy', [EventAdminController::class, 'bulkDestroy'])->name('admin.events.bulk-destroy');
        Route::post('/admin/events/import-csv', [EventAdminController::class, 'importCsv'])->name('admin.events.import-csv');
        Route::get('/admin/public-notices', [SitePublicNoticeAdminController::class, 'index'])->name('admin.public-notices.index');
        Route::get('/admin/public-notices/create', [SitePublicNoticeAdminController::class, 'create'])->name('admin.public-notices.create');
        Route::post('/admin/public-notices', [SitePublicNoticeAdminController::class, 'store'])->name('admin.public-notices.store');
        Route::get('/admin/public-notices/{id}/edit', [SitePublicNoticeAdminController::class, 'edit'])->name('admin.public-notices.edit');
        Route::put('/admin/public-notices/{id}', [SitePublicNoticeAdminController::class, 'update'])->name('admin.public-notices.update');
        Route::delete('/admin/public-notices/{id}', [SitePublicNoticeAdminController::class, 'destroy'])->name('admin.public-notices.destroy');
        Route::get('/admin/support', [SupportMessageAdminController::class, 'index'])->name('admin.support.index');
        Route::post('/admin/support/auto-reply', [SupportMessageAdminController::class, 'updateAutoReply'])->name('admin.support.auto-reply');
        Route::post('/admin/support/new', [SupportMessageAdminController::class, 'storeNew'])->name('admin.support.new');
        Route::get('/admin/support/{userId}', [SupportMessageAdminController::class, 'show'])->name('admin.support.show');
        Route::post('/admin/support/{userId}', [SupportMessageAdminController::class, 'store'])->name('admin.support.store');
    });
    
    // ユーザー自身の月次限度時間関連（セッション認証用）
    Route::get('/api/users/me/monthly-limit', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimit']);
    Route::get('/api/users/me/monthly-limits', [\App\Http\Controllers\Api\UserController::class, 'getMyMonthlyLimits']);

    // マッチング関連（セッション認証用）
    Route::post('/api/matchings/accept', [\App\Http\Controllers\Api\MatchingController::class, 'accept']);
    Route::post('/api/matchings/decline', [\App\Http\Controllers\Api\MatchingController::class, 'decline']);

    // 依頼関連（セッション認証用）
    Route::get('/api/requests/guide/available', [\App\Http\Controllers\Api\RequestController::class, 'availableForGuide']);

    // 個人カレンダー（本人のみ）
    Route::get('/calendar/personal', [PersonalCalendarController::class, 'index'])->name('calendar.personal.index');
    Route::get('/calendar/personal/new', [PersonalCalendarController::class, 'create'])->name('calendar.personal.create');
    Route::post('/calendar/personal', [PersonalCalendarController::class, 'store'])->name('calendar.personal.store');
    Route::get('/calendar/personal/{id}/edit', [PersonalCalendarController::class, 'edit'])->name('calendar.personal.edit');
    Route::put('/calendar/personal/{id}', [PersonalCalendarController::class, 'update'])->name('calendar.personal.update');
    Route::delete('/calendar/personal/{id}', [PersonalCalendarController::class, 'destroy'])->name('calendar.personal.destroy');

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/events/{id}/admin-publish', [EventController::class, 'adminPublish'])->name('events.admin.publish');
        Route::post('/events/{id}/admin-cancel', [EventController::class, 'adminCancel'])->name('events.admin.cancel');
    });
});

