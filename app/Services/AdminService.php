<?php

namespace App\Services;

use App\Models\Request;
use App\Models\GuideAcceptance;
use App\Models\Report;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\GuideProfile;
use App\Models\AdminSetting;
use App\Models\Matching;
use App\Models\Notification;
use App\Models\UserMonthlyLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use App\Services\UserMonthlyLimitService;
use App\Services\EmailNotificationService;

class AdminService
{
    protected EmailNotificationService $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function getDashboardData(?User $adminUser = null): array
    {
        $data = [
            'requests' => $this->getAllRequests(),
            'acceptances' => $this->getPendingAcceptances(),
            'reports' => $this->getPendingReports(),
            'stats' => $this->getStats(),
            'autoMatching' => $this->getAutoMatchingSetting(),
        ];
        if ($adminUser) {
            $data['notifications'] = $this->getAdminNotifications($adminUser->id, 5);
        } else {
            $data['notifications'] = [];
        }
        return $data;
    }

    /**
     * 管理者向けの未読通知を取得（新規登録・報告書承認待ち・承諾など）
     */
    public function getAdminNotifications(int $adminUserId, int $limit = 5): array
    {
        return Notification::where('user_id', $adminUserId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'related_id' => $n->related_id,
                    'created_at' => $n->created_at?->toIso8601String() ?? $n->created_at,
                ];
            })
            ->toArray();
    }

    public function getAllRequests(): array
    {
        $requests = Request::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // リレーションを明示的にロード
        $requests->each(function ($request) {
            $request->user;
        });
        
        return $requests->map(function($request) {
                return [
                    'id' => (int) $request->id,
                    'user_id' => (int) $request->user_id,
                    'user_name' => $request->user->name ?? '',
                    'user_email' => $request->user->email ?? '',
                    'request_type' => $request->request_type,
                    'masked_address' => $request->masked_address,
                    'request_date' => $request->request_date,
                    'request_time' => $request->request_time,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                ];
            })
            ->toArray();
    }

    public function getPendingAcceptances(): array
    {
        $acceptances = GuideAcceptance::where('status', 'pending')
            ->with(['request:id,user_id,request_type,masked_address,request_date,request_time', 'guide:id,name', 'request.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // リレーションを明示的にロード
        $acceptances->each(function ($acceptance) {
            $acceptance->request;
            $acceptance->guide;
            if ($acceptance->request) {
                $acceptance->request->user;
            }
        });
        
        return $acceptances->map(function($acceptance) {
                // user_selectedをboolean型で確実に取得
                $userSelected = $acceptance->user_selected;
                if (is_null($userSelected)) {
                    $userSelected = false;
                } elseif (is_string($userSelected)) {
                    // 文字列の場合はbooleanに変換
                    $userSelected = filter_var($userSelected, FILTER_VALIDATE_BOOLEAN);
                } elseif (is_int($userSelected)) {
                    // 整数の場合はbooleanに変換（0=false, 1=true）
                    $userSelected = (bool) $userSelected;
                }
                
                return [
                    'id' => (int) $acceptance->id,
                    'request_id' => (int) $acceptance->request_id,
                    'guide_id' => (int) $acceptance->guide_id,
                    'request_type' => $acceptance->request->request_type ?? '',
                    'masked_address' => $acceptance->request->masked_address ?? '',
                    'request_date' => $acceptance->request->request_date ?? '',
                    'request_time' => $acceptance->request->request_time ?? '',
                    'user_name' => $acceptance->request->user->name ?? '',
                    'guide_name' => $acceptance->guide->name ?? '',
                    'status' => $acceptance->status,
                    'admin_decision' => $acceptance->admin_decision,
                    'user_selected' => (bool) $userSelected, // boolean型で確実に返す
                    'created_at' => $acceptance->created_at,
                ];
            })
            ->toArray();
    }

    public function getPendingReports(): array
    {
        // 報告書一覧（全ステータス）を取得
        return Report::with(['user:id,name', 'guide:id,name', 'request:id,request_type'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getUserApprovedReports(): array
    {
        // 管理者承認待ちの報告書を取得
        return Report::where('status', 'user_approved')
            ->with(['user:id,name', 'guide:id,name', 'request:id,request_type'])
            ->orderBy('user_approved_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getStats(): array
    {
        // マッチング統計
        // 完了数には status = 'completed' または report_completed_at が設定されているマッチングを含める
        $matchingStats = DB::table('matchings')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'matched' AND report_completed_at IS NULL THEN 1 ELSE 0 END) as matched,
                SUM(CASE WHEN status = 'in_progress' AND report_completed_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' OR report_completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        // 依頼統計
        $requestStats = DB::table('requests')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'guide_accepted' THEN 1 ELSE 0 END) as guide_accepted,
                SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        return [
            'matchings' => [
                'total' => (int)($matchingStats->total ?? 0),
                'matched' => (int)($matchingStats->matched ?? 0),
                'in_progress' => (int)($matchingStats->in_progress ?? 0),
                'completed' => (int)($matchingStats->completed ?? 0),
                'cancelled' => (int)($matchingStats->cancelled ?? 0),
            ],
            'requests' => [
                'total' => (int)($requestStats->total ?? 0),
                'pending' => (int)($requestStats->pending ?? 0),
                'guide_accepted' => (int)($requestStats->guide_accepted ?? 0),
                'matched' => (int)($requestStats->matched ?? 0),
                'in_progress' => (int)($requestStats->in_progress ?? 0),
                'completed' => (int)($requestStats->completed ?? 0),
                'cancelled' => (int)($requestStats->cancelled ?? 0),
            ],
        ];
    }

    public function getUserStats(): array
    {
        // ユーザー統計
        $userStats = DB::table('users')
            ->where('role', 'user')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN is_allowed = true THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN is_allowed = false THEN 1 ELSE 0 END) as pending
            ")
            ->first();

        // ガイド統計
        $guideStats = DB::table('users')
            ->where('role', 'guide')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN is_allowed = true THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN is_allowed = false THEN 1 ELSE 0 END) as pending
            ")
            ->first();

        return [
            'users' => [
                'total' => (int)($userStats->total ?? 0),
                'approved' => (int)($userStats->approved ?? 0),
                'pending' => (int)($userStats->pending ?? 0),
            ],
            'guides' => [
                'total' => (int)($guideStats->total ?? 0),
                'approved' => (int)($guideStats->approved ?? 0),
                'pending' => (int)($guideStats->pending ?? 0),
            ],
        ];
    }

    public function getAutoMatchingSetting(): bool
    {
        return AdminSetting::where('setting_key', 'auto_matching')
            ->value('setting_value') === 'true';
    }

    public function updateAutoMatching(bool $enabled): void
    {
        AdminSetting::updateOrCreate(
            ['setting_key' => 'auto_matching'],
            ['setting_value' => $enabled ? 'true' : 'false']
        );
    }

    public function approveMatching(int $requestId, int $guideId): Matching
    {
        $request = Request::findOrFail($requestId);
        
        $matchingService = app(\App\Services\MatchingService::class);
        return $matchingService->createMatching($requestId, $request->user_id, $guideId);
    }

    public function batchApproveMatchings(array $matchings): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($matchings as $matchingData) {
            try {
                $requestId = $matchingData['request_id'];
                $guideId = $matchingData['guide_id'];
                
                $matching = $this->approveMatching($requestId, $guideId);
                $results['success'][] = [
                    'request_id' => $requestId,
                    'guide_id' => $guideId,
                    'matching_id' => $matching->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'request_id' => $matchingData['request_id'] ?? null,
                    'guide_id' => $matchingData['guide_id'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function rejectMatching(int $requestId, int $guideId): void
    {
        GuideAcceptance::where('request_id', $requestId)
            ->where('guide_id', $guideId)
            ->update([
                'status' => 'rejected',
                'admin_decision' => 'rejected',
            ]);

        // 依頼ステータスをpendingに戻す
        Request::where('id', $requestId)->update(['status' => 'pending']);
    }

    public function getReportForCsv(int $reportId): ?array
    {
        $report = Report::where('id', $reportId)
            ->with([
                'user:id,name,email',
                'guide:id,name,email',
                'request:id,request_type,request_date',
                'user.userProfile:id,user_id,recipient_number',
                'guide.guideProfile:id,user_id,employee_number'
            ])
            ->first();
        
        if (!$report) {
            return null;
        }
        
        // リレーションを明示的にロード
        $report->user;
        $report->guide;
        $report->request;
        if ($report->user) {
            $report->user->userProfile;
        }
        if ($report->guide) {
            $report->guide->guideProfile;
        }
        
        $startTime = $report->actual_start_time 
            ? (is_string($report->actual_start_time) 
                ? substr($report->actual_start_time, 0, 5) 
                : $report->actual_start_time->format('H:i'))
            : '';
        
        $endTime = $report->actual_end_time 
            ? (is_string($report->actual_end_time) 
                ? substr($report->actual_end_time, 0, 5) 
                : $report->actual_end_time->format('H:i'))
            : '';
        
        return [
            'id' => $report->id,
            'actual_date' => $report->actual_date ? $report->actual_date->format('Y-m-d') : '',
            'actual_start_time' => $startTime,
            'actual_end_time' => $endTime,
            'user_name' => $report->user->name ?? '',
            'user_email' => $report->user->email ?? '',
            'recipient_number' => $report->user->userProfile->recipient_number ?? '',
            'guide_name' => $report->guide->name ?? '',
            'guide_email' => $report->guide->email ?? '',
            'employee_number' => $report->guide->guideProfile->employee_number ?? '',
            'request_type' => $report->request->request_type ?? '',
            'request_date' => $report->request->request_date ?? '',
            'approved_at' => $report->approved_at ? $report->approved_at->format('Y-m-d H:i:s') : '',
            'service_content' => $report->service_content ?? '',
            'report_content' => $report->report_content ?? '',
        ];
    }

    public function getReportsForCsv(): array
    {
        $reports = Report::whereIn('status', ['admin_approved', 'approved'])
            ->with([
                'user:id,name,email',
                'guide:id,name,email',
                'request:id,request_type,request_date',
                'user.userProfile:id,user_id,recipient_number',
                'guide.guideProfile:id,user_id,employee_number'
            ])
            ->orderBy('approved_at', 'desc')
            ->get();
        
        // リレーションを明示的にロード
        $reports->each(function ($report) {
            $report->user;
            $report->guide;
            $report->request;
            if ($report->user) {
                $report->user->userProfile;
            }
            if ($report->guide) {
                $report->guide->guideProfile;
            }
        });
        
        return $reports->map(function($report) {
                return [
                    'id' => $report->id,
                    'actual_date' => $report->actual_date ? $report->actual_date->format('Y-m-d') : '',
                    'actual_start_time' => $report->actual_start_time ? $report->actual_start_time->format('H:i') : '',
                    'actual_end_time' => $report->actual_end_time ? $report->actual_end_time->format('H:i') : '',
                    'user_name' => $report->user->name ?? '',
                    'user_email' => $report->user->email ?? '',
                    'recipient_number' => $report->user->userProfile->recipient_number ?? '',
                    'guide_name' => $report->guide->name ?? '',
                    'guide_email' => $report->guide->email ?? '',
                    'employee_number' => $report->guide->guideProfile->employee_number ?? '',
                    'request_type' => $report->request->request_type ?? '',
                    'request_date' => $report->request->request_date ?? '',
                    'approved_at' => $report->approved_at ? $report->approved_at->format('Y/m/d H:i:s') : '',
                ];
            })
            ->toArray();
    }

    public function getUsageForCsv(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Report::whereIn('status', ['admin_approved', 'approved'])
            ->with([
                'user:id,name',
                'guide:id,name',
                'request:id,request_type',
                'user.userProfile:id,user_id,recipient_number',
                'guide.guideProfile:id,user_id,employee_number'
            ]);

        if ($startDate) {
            $query->where('actual_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('actual_date', '<=', $endDate);
        }

        $reports = $query->orderBy('actual_date', 'desc')->get();
        
        // リレーションを明示的にロード
        $reports->each(function ($report) {
            $report->user;
            $report->guide;
            $report->request;
            if ($report->user) {
                $report->user->userProfile;
            }
            if ($report->guide) {
                $report->guide->guideProfile;
            }
        });

        return $reports->map(function($report) {
                $startTime = $report->actual_start_time;
                $endTime = $report->actual_end_time;
                $durationMinutes = 0;
                if ($startTime && $endTime && $report->actual_date) {
                    $start = \Carbon\Carbon::parse($report->actual_date->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
                    $end = \Carbon\Carbon::parse($report->actual_date->format('Y-m-d') . ' ' . $endTime->format('H:i:s'));
                    $durationMinutes = $start->diffInMinutes($end);
                }

                return [
                    'id' => $report->id,
                    'actual_date' => $report->actual_date ? $report->actual_date->format('Y-m-d') : '',
                    'actual_start_time' => $report->actual_start_time ? $report->actual_start_time->format('H:i') : '',
                    'actual_end_time' => $report->actual_end_time ? $report->actual_end_time->format('H:i') : '',
                    'duration_minutes' => $durationMinutes,
                    'user_name' => $report->user->name ?? '',
                    'recipient_number' => $report->user->userProfile->recipient_number ?? '',
                    'guide_name' => $report->guide->name ?? '',
                    'employee_number' => $report->guide->guideProfile->employee_number ?? '',
                    'request_type' => $report->request->request_type ?? '',
                ];
            })
            ->toArray();
    }

    /**
     * 利用者一覧を取得（並び替え・検索対応）
     *
     * @param string $sort 並び順: pending_first | created_desc | created_asc | name_asc | name_desc
     * @param string|null $search 検索文字列（名前・メールアドレスで部分一致）
     */
    public function getAllUsers(string $sort = 'created_desc', ?string $search = null): array
    {
        $query = User::where('role', 'user')
            ->with('userProfile:id,user_id,contact_method,notes,recipient_number,admin_comment');

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        switch ($sort) {
            case 'pending_first':
                // 未承認(is_allowed=false)を先に、その後登録が新しい順
                $query->orderBy('is_allowed', 'asc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'created_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $users = $query->get();

        // リレーションを明示的にロード
        $users->each(function ($user) {
            $user->userProfile;
        });

        return $users->map(function($user) {
                return [
                    'id' => (int) $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'birth_date' => $user->birth_date,
                    'age' => $user->age,
                    'role' => $user->role,
                    'is_allowed' => $user->is_allowed,
                    'created_at' => $user->created_at,
                    'contact_method' => $user->userProfile->contact_method ?? null,
                    'notes' => $user->userProfile->notes ?? null,
                    'introduction' => $user->userProfile->introduction ?? null,
                    'recipient_number' => $user->userProfile->recipient_number ?? null,
                    'admin_comment' => $user->userProfile->admin_comment ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * 全利用者の指定月の限度時間・使用時間・残時間を一覧で取得（照会用・CSV用）
     *
     * @param int|null $year 年（省略時は当年）
     * @param int|null $month 月（省略時は当月）
     * @return array 各要素: user_id, user_name, email, recipient_number, year, month, limit_hours, used_hours, remaining_hours
     */
    public function getAllUsersMonthlyLimitsSummary(?int $year = null, ?int $month = null): array
    {
        $now = Carbon::now();
        $year = $year ?? $now->year;
        $month = $month ?? $now->month;

        $users = User::where('role', 'user')
            ->with('userProfile:id,user_id,recipient_number')
            ->orderBy('name')
            ->get();

        $limitsByUser = UserMonthlyLimit::where('year', $year)
            ->where('month', $month)
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id');

        return $users->map(function ($user) use ($year, $month, $limitsByUser) {
            $userLimits = $limitsByUser->get($user->id) ?? collect();
            $outing = $userLimits->firstWhere('request_type', 'outing');
            $home = $userLimits->firstWhere('request_type', 'home');
            $build = function ($row) {
                if (!$row) {
                    return ['limit_hours' => 0.0, 'used_hours' => 0.0, 'remaining_hours' => 0.0];
                }
                $limit = (float) $row->limit_hours;
                $used = (float) $row->used_hours;
                return [
                    'limit_hours' => round($limit, 2),
                    'used_hours' => round($used, 2),
                    'remaining_hours' => round(max(0, $limit - $used), 2),
                ];
            };

            return [
                'user_id' => (int) $user->id,
                'user_name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'recipient_number' => $user->userProfile->recipient_number ?? '',
                'year' => $year,
                'month' => $month,
                'outing' => $build($outing),
                'home' => $build($home),
                // 後方互換: 合計
                'limit_hours' => round($build($outing)['limit_hours'] + $build($home)['limit_hours'], 2),
                'used_hours' => round($build($outing)['used_hours'] + $build($home)['used_hours'], 2),
                'remaining_hours' => round($build($outing)['remaining_hours'] + $build($home)['remaining_hours'], 2),
            ];
        })->values()->toArray();
    }

    /**
     * ガイド一覧を取得（並び替え・検索対応）
     *
     * @param string $sort 並び順: pending_first | created_desc | created_asc | name_asc | name_desc
     * @param string|null $search 検索文字列（名前・メールアドレスで部分一致）
     */
    public function getAllGuides(string $sort = 'created_desc', ?string $search = null): array
    {
        $query = User::where('role', 'guide')
            ->with('guideProfile:id,user_id,introduction,available_areas,available_days,available_times,employee_number,admin_comment');

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        switch ($sort) {
            case 'pending_first':
                $query->orderBy('is_allowed', 'asc')
                    ->orderBy('created_at', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'created_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $guides = $query->get();

        // リレーションを明示的にロード
        $guides->each(function ($guide) {
            $guide->guideProfile;
        });

        return $guides->map(function($guide) {
                $profile = $guide->guideProfile;
                return [
                    'id' => $guide->id,
                    'email' => $guide->email,
                    'name' => $guide->name,
                    'phone' => $guide->phone,
                    'address' => $guide->address,
                    'birth_date' => $guide->birth_date,
                    'age' => $guide->age,
                    'role' => $guide->role,
                    'is_allowed' => $guide->is_allowed,
                    'created_at' => $guide->created_at,
                    'introduction' => $profile->introduction ?? null,
                    'available_areas' => $profile->available_areas ?? [],
                    'available_days' => $profile->available_days ?? [],
                    'available_times' => $profile->available_times ?? [],
                    'employee_number' => $profile->employee_number ?? null,
                    'admin_comment' => $profile->admin_comment ?? null,
                ];
            })
            ->toArray();
    }

    public function updateUserProfileExtra(int $userId, ?string $recipientNumber, ?string $adminComment): void
    {
        $user = User::where('id', $userId)->where('role', 'user')->firstOrFail();
        
        UserProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'recipient_number' => $recipientNumber,
                'admin_comment' => $adminComment,
            ]
        );
    }

    public function updateUserProfile(int $userId, array $data): void
    {
        $user = User::where('id', $userId)->where('role', 'user')->firstOrFail();
        
        // 基本情報の更新
        $userData = [];
        if (isset($data['name'])) {
            $userData['name'] = $data['name'];
        }
        if (isset($data['phone'])) {
            $userData['phone'] = $data['phone'];
        }
        if (isset($data['address'])) {
            $userData['address'] = $data['address'];
        }
        if (!empty($userData)) {
            $user->update($userData);
        }
        
        // プロフィール情報の更新
        $profileData = [];
        if (isset($data['contact_method'])) {
            $profileData['contact_method'] = $data['contact_method'];
        }
        if (isset($data['notes'])) {
            $profileData['notes'] = $data['notes'];
        }
        if (isset($data['introduction'])) {
            $profileData['introduction'] = $data['introduction'];
        }
        if (isset($data['recipient_number'])) {
            $profileData['recipient_number'] = $data['recipient_number'];
        }
        if (isset($data['admin_comment'])) {
            $profileData['admin_comment'] = $data['admin_comment'];
        }
        
        if (!empty($profileData)) {
            UserProfile::updateOrCreate(
                ['user_id' => $userId],
                $profileData
            );
        }
    }

    public function updateGuideProfileExtra(int $guideId, ?string $employeeNumber): void
    {
        $guide = User::where('id', $guideId)->where('role', 'guide')->firstOrFail();
        
        GuideProfile::updateOrCreate(
            ['user_id' => $guideId],
            ['employee_number' => $employeeNumber]
        );
    }

    public function updateGuideProfile(int $guideId, array $data): void
    {
        $guide = User::where('id', $guideId)->where('role', 'guide')->firstOrFail();
        
        // 基本情報の更新
        $userData = [];
        if (isset($data['name'])) {
            $userData['name'] = $data['name'];
        }
        if (isset($data['phone'])) {
            $userData['phone'] = $data['phone'];
        }
        if (isset($data['address'])) {
            $userData['address'] = $data['address'];
        }
        if (!empty($userData)) {
            $guide->update($userData);
        }
        
        // プロフィール情報の更新
        $profileData = [];
        if (isset($data['introduction'])) {
            $profileData['introduction'] = $data['introduction'];
        }
        if (isset($data['available_areas'])) {
            $profileData['available_areas'] = $data['available_areas'];
        }
        if (isset($data['available_days'])) {
            $profileData['available_days'] = $data['available_days'];
        }
        if (isset($data['available_times'])) {
            $profileData['available_times'] = $data['available_times'];
        }
        if (isset($data['employee_number'])) {
            $profileData['employee_number'] = $data['employee_number'];
        }
        if (isset($data['admin_comment'])) {
            $profileData['admin_comment'] = $data['admin_comment'];
        }
        
        if (!empty($profileData)) {
            GuideProfile::updateOrCreate(
                ['user_id' => $guideId],
                $profileData
            );
        }
    }

    public function approveUser(int $userId): void
    {
        $user = User::where('id', $userId)->where('role', 'user')->firstOrFail();
        
        $user->update(['is_allowed' => true]);

        // 画面上の通知を送信
        Notification::create([
            'user_id' => $userId,
            'type' => 'approval',
            'title' => 'アカウントが承認されました',
            'message' => 'あなたのアカウントが承認されました。ログインできるようになりました。',
            'related_id' => $userId,
        ]);

        // メール通知を送信
        $this->emailService->sendAccountApprovedNotification($user, false);
    }

    public function approveGuide(int $guideId): void
    {
        $guide = User::where('id', $guideId)->where('role', 'guide')->firstOrFail();
        
        $guide->update(['is_allowed' => true]);

        // 画面上の通知を送信
        Notification::create([
            'user_id' => $guideId,
            'type' => 'approval',
            'title' => 'アカウントが承認されました',
            'message' => 'あなたのアカウントが承認されました。ログインできるようになりました。',
            'related_id' => $guideId,
        ]);

        // メール通知を送信
        $this->emailService->sendAccountApprovedNotification($guide, true);
    }

    public function rejectUser(int $userId): void
    {
        $user = User::where('id', $userId)->where('role', 'user')->firstOrFail();
        
        $user->update(['is_allowed' => false]);

        // 通知を送信
        Notification::create([
            'user_id' => $userId,
            'type' => 'approval',
            'title' => 'アカウントが拒否されました',
            'message' => '申し訳ございませんが、あなたのアカウントは承認されませんでした。',
            'related_id' => $userId,
        ]);
    }

    public function rejectGuide(int $guideId): void
    {
        $guide = User::where('id', $guideId)->where('role', 'guide')->firstOrFail();
        
        $guide->update(['is_allowed' => false]);

        // 通知を送信
        Notification::create([
            'user_id' => $guideId,
            'type' => 'approval',
            'title' => 'アカウントが拒否されました',
            'message' => '申し訳ございませんが、あなたのアカウントは承認されませんでした。',
            'related_id' => $guideId,
        ]);
    }

    /**
     * CSV一括登録で使用するヘッダー（テンプレート用）
     */
    public static function getBulkImportCsvHeaders(): array
    {
        return [
            'role', 'email', 'password', 'name', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana',
            'birth_date', 'age', 'gender', 'address', 'postal_code', 'phone',
            'is_allowed', 'email_confirmed',
            'recipient_number', 'contact_method', 'notes', 'introduction', 'admin_comment',
            'interview_date_1', 'interview_date_2', 'interview_date_3', 'application_reason', 'visual_disability_status', 'disability_support_level', 'daily_life_situation',
            'employee_number', 'available_areas', 'available_days', 'available_times', 'goal', 'qualifications', 'preferred_work_hours',
            'limit_year', 'limit_month', 'limit_outing_hours', 'limit_home_hours',
        ];
    }

    /**
     * CSV一括登録テンプレート（BOM付きUTF-8）を返す
     */
    public function getBulkImportCsvTemplate(): string
    {
        $headers = self::getBulkImportCsvHeaders();
        $comment = '# role: user または guide。ユーザーは recipient_number 等・限度時間、ガイドは employee_number 等を記載。日付は Y-m-d または Y-m-d H:i:s。available_areas/days/times はカンマ区切り。';
        $csv = "\xEF\xBB\xBF" . $comment . "\n" . implode(',', array_map(function ($h) {
            return '"' . str_replace('"', '""', $h) . '"';
        }, $headers)) . "\n";
        $exampleUser = [
            'user', 'user@example.com', 'password6', '山田 太郎', '山田', '太郎', 'ヤマダ', 'タロウ',
            '1990-01-15', '34', 'male', '東京都渋谷区', '150-0000', '090-1234-5678',
            '0', '0',
            '12345678', '', '', 'よろしくお願いします', '',
            '', '', '', '知人の紹介', '', '', '',
            '', '', '', '', '', '', '',
            (string) date('Y'), (string) date('n'), '50', '40',
        ];
        $csv .= implode(',', array_map(function ($v) {
            return '"' . str_replace('"', '""', (string) $v) . '"';
        }, $exampleUser)) . "\n";
        return $csv;
    }

    /**
     * CSV一括登録を処理する。戻り値: ['created' => int, 'errors' => [行番号 => [メッセージ]]]
     *
     * @param UploadedFile $file CSVファイル
     * @return array{created: int, errors: array<int, array<string>>}
     */
    public function processBulkImportCsv(UploadedFile $file): array
    {
        $created = 0;
        $errors = [];
        $limitService = app(UserMonthlyLimitService::class);
        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            return ['created' => 0, 'errors' => [0 => ['CSVファイルを開けませんでした。']]];
        }
        $bom = fread($stream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($stream);
        }
        $firstRow = fgetcsv($stream, 0, ',', '"', '');
        if ($firstRow === false) {
            fclose($stream);
            return ['created' => 0, 'errors' => [0 => ['CSVのヘッダーを読み取れませんでした。']]];
        }
        $firstCol = isset($firstRow[0]) ? trim((string) $firstRow[0]) : '';
        if ($firstCol === '' || strpos($firstCol, '#') === 0) {
            $headerRow = fgetcsv($stream, 0, ',', '"', '');
            if ($headerRow === false) {
                fclose($stream);
                return ['created' => 0, 'errors' => [0 => ['CSVのヘッダーを読み取れませんでした。']]];
            }
        } else {
            $headerRow = $firstRow;
        }
        $headerRow = array_map(function ($c) {
            return trim(preg_replace('/^#.*/', '', (string) $c));
        }, $headerRow);
        $expectedHeaders = self::getBulkImportCsvHeaders();
        $colIndex = [];
        foreach ($expectedHeaders as $i => $key) {
            $colIndex[$key] = array_search($key, $headerRow, true);
            if ($colIndex[$key] === false && in_array($key, ['role', 'email', 'password', 'name'], true)) {
                fclose($stream);
                return ['created' => 0, 'errors' => [0 => ['必須列が見つかりません: ' . $key]]];
            }
        }
        $lineNo = 1;
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            $lineNo++;
            $get = function ($key) use ($row, $colIndex) {
                $i = $colIndex[$key] ?? -1;
                if ($i < 0 || !isset($row[$i])) {
                    return '';
                }
                return trim((string) $row[$i]);
            };
            $role = $get('role');
            if ($role === '' || (strtolower($role) !== 'user' && strtolower($role) !== 'guide')) {
                $errors[$lineNo] = ['role は user または guide を指定してください。'];
                continue;
            }
            $role = strtolower($role);
            $email = $get('email');
            $password = $get('password');
            $name = $get('name');
            if ($email === '') {
                $errors[$lineNo] = ['email は必須です。'];
                continue;
            }
            if (User::where('email', $email)->exists()) {
                $errors[$lineNo] = ['このメールアドレスは既に登録されています。'];
                continue;
            }
            if (strlen($password) < 6) {
                $errors[$lineNo] = ['password は6文字以上で入力してください。'];
                continue;
            }
            $fullName = $name !== '' ? $name : trim($get('last_name') . ' ' . $get('first_name'));
            if ($fullName === '') {
                $errors[$lineNo] = ['name または last_name/first_name を入力してください。'];
                continue;
            }
            $today = new \DateTime();
            $birthDate = $get('birth_date');
            $age = $get('age');
            if ($birthDate !== '') {
                $d = \DateTime::createFromFormat('Y-m-d', $birthDate);
                if ($d) {
                    $age = (string) (int) $today->diff($d)->y;
                }
            }
            $gender = $get('gender');
            if (!in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say'], true)) {
                $gender = null;
            }
            try {
                DB::beginTransaction();
                $user = User::create([
                    'email' => $email,
                    'password_hash' => Hash::make($password),
                    'name' => $fullName,
                    'last_name' => $get('last_name') ?: null,
                    'first_name' => $get('first_name') ?: null,
                    'last_name_kana' => $get('last_name_kana') ?: null,
                    'first_name_kana' => $get('first_name_kana') ?: null,
                    'birth_date' => $birthDate !== '' ? $birthDate : null,
                    'age' => $age !== '' ? (int) $age : null,
                    'gender' => $gender,
                    'address' => $get('address') ?: null,
                    'postal_code' => $get('postal_code') ?: null,
                    'phone' => $get('phone') ?: null,
                    'role' => $role,
                    'is_allowed' => (int) $get('is_allowed') === 1,
                    'email_confirmed' => (int) $get('email_confirmed') === 1,
                ]);
                if ($role === 'user') {
                    $interview1 = $this->parseDateTime($get('interview_date_1'));
                    $interview2 = $this->parseDateTime($get('interview_date_2'));
                    $interview3 = $this->parseDateTime($get('interview_date_3'));
                    UserProfile::create([
                        'user_id' => $user->id,
                        'recipient_number' => $get('recipient_number') ?: null,
                        'contact_method' => $get('contact_method') ?: null,
                        'notes' => $get('notes') ?: null,
                        'introduction' => $get('introduction') ?: null,
                        'admin_comment' => $get('admin_comment') ?: null,
                        'interview_date_1' => $interview1,
                        'interview_date_2' => $interview2,
                        'interview_date_3' => $interview3,
                        'application_reason' => $get('application_reason') ?: null,
                        'visual_disability_status' => $get('visual_disability_status') ?: null,
                        'disability_support_level' => $get('disability_support_level') ?: null,
                        'daily_life_situation' => $get('daily_life_situation') ?: null,
                    ]);
                    $limitYear = $get('limit_year') !== '' ? (int) $get('limit_year') : (int) date('Y');
                    $limitMonth = $get('limit_month') !== '' ? (int) $get('limit_month') : (int) date('n');
                    $outingHours = $get('limit_outing_hours') !== '' ? (float) $get('limit_outing_hours') : 0;
                    $homeHours = $get('limit_home_hours') !== '' ? (float) $get('limit_home_hours') : 0;
                    if ($outingHours > 0 || $homeHours > 0) {
                        $limitService->setLimit($user->id, $outingHours, $limitYear, $limitMonth, 'outing');
                        $limitService->setLimit($user->id, $homeHours, $limitYear, $limitMonth, 'home');
                    }
                } else {
                    $areas = $get('available_areas') !== '' ? array_map('trim', explode(',', $get('available_areas'))) : [];
                    $days = $get('available_days') !== '' ? array_map('trim', explode(',', $get('available_days'))) : [];
                    $times = $get('available_times') !== '' ? array_map('trim', explode(',', $get('available_times'))) : [];
                    $qualRaw = $get('qualifications');
                    $qualifications = [];
                    if ($qualRaw !== '') {
                        $decoded = json_decode($qualRaw, true);
                        if (is_array($decoded)) {
                            $qualifications = $decoded;
                        } else {
                            foreach (array_map('trim', explode(',', $qualRaw)) as $q) {
                                if ($q !== '') {
                                    $qualifications[] = ['name' => $q, 'obtained_date' => null];
                                }
                            }
                        }
                    }
                    GuideProfile::create([
                        'user_id' => $user->id,
                        'introduction' => $get('introduction') ?: null,
                        'available_areas' => $areas,
                        'available_days' => $days,
                        'available_times' => $times,
                        'employee_number' => $get('employee_number') ?: null,
                        'admin_comment' => $get('admin_comment') ?: null,
                        'application_reason' => $get('application_reason') ?: null,
                        'goal' => $get('goal') ?: null,
                        'qualifications' => $qualifications,
                        'preferred_work_hours' => $get('preferred_work_hours') ?: null,
                    ]);
                }
                DB::commit();
                $created++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[$lineNo] = [$e->getMessage()];
            }
        }
        fclose($stream);
        return ['created' => $created, 'errors' => $errors];
    }

    private function parseDateTime(?string $value): ?\DateTime
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
        foreach ($formats as $f) {
            $d = \DateTime::createFromFormat($f, trim($value));
            if ($d) {
                return $d;
            }
        }
        return null;
    }
}

