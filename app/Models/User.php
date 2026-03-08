<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'age',
        'gender',
        'address',
        'postal_code',
        'phone',
        'birth_date',
        'role',
        'is_allowed',
        'email_confirmed',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'email_confirmed' => 'boolean',
        'birth_date' => 'date',
    ];

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function guideProfile()
    {
        return $this->hasOne(GuideProfile::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function guideAcceptances()
    {
        return $this->hasMany(GuideAcceptance::class, 'guide_id');
    }

    public function matchingsAsUser()
    {
        return $this->hasMany(Matching::class, 'user_id');
    }

    public function matchingsAsGuide()
    {
        return $this->hasMany(Matching::class, 'guide_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function reportsAsGuide()
    {
        return $this->hasMany(Report::class, 'guide_id');
    }

    public function reportsAsUser()
    {
        return $this->hasMany(Report::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isGuide()
    {
        return $this->role === 'guide';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function blockedUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blockedByUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    public function hasBlocked(int $userId): bool
    {
        return $this->blockedUsers()->where('blocked_id', $userId)->exists();
    }

    public function isBlockedBy(int $userId): bool
    {
        return $this->blockedByUsers()->where('blocker_id', $userId)->exists();
    }

    public function getBlockedUserIds(): array
    {
        return $this->blockedUsers()->pluck('blocked_id')->toArray();
    }

    public function getBlockedByUserIds(): array
    {
        return $this->blockedByUsers()->pluck('blocker_id')->toArray();
    }

    /**
     * 自分がした評価
     */
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    /**
     * 自分が受けた評価
     */
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    /**
     * 平均評価を取得
     */
    public function getAverageRating(): ?float
    {
        $avg = $this->receivedRatings()->avg('score');
        return $avg ? round($avg, 1) : null;
    }

    /**
     * 評価件数を取得
     */
    public function getRatingCount(): int
    {
        return $this->receivedRatings()->count();
    }

    /**
     * 最新の評価コメントを取得
     */
    public function getLatestRatingComments(int $limit = 3)
    {
        return $this->receivedRatings()
            ->with('rater')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 直前3日以内のキャンセル率を計算
     * キャンセル日から依頼日までが3日以内のマッチングを対象
     */
    public function getLateCancellationRate(): array
    {
        $userIdColumn = $this->isGuide() ? 'guide_id' : 'user_id';
        $cancelledByValue = $this->isGuide() ? 'guide' : 'user';
        
        // 全てのマッチング数（キャンセル・完了含む）
        $totalMatchings = Matching::where($userIdColumn, $this->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->count();
        
        if ($totalMatchings === 0) {
            return [
                'rate' => null,
                'total' => 0,
                'late_cancels' => 0,
            ];
        }
        
        // 直前3日以内にキャンセルしたマッチング数
        // (依頼日 - キャンセル日 <= 3日)
        $lateCancellations = Matching::where($userIdColumn, $this->id)
            ->where('status', 'cancelled')
            ->where('cancelled_by', $cancelledByValue)
            ->whereNotNull('cancelled_at')
            ->whereHas('request', function ($query) {
                $query->whereRaw("request_date - CAST(matchings.cancelled_at AS DATE) <= 3");
            })
            ->count();
        
        $rate = $totalMatchings > 0 ? round(($lateCancellations / $totalMatchings) * 100, 1) : 0;
        
        return [
            'rate' => $rate,
            'total' => $totalMatchings,
            'late_cancels' => $lateCancellations,
        ];
    }

    /**
     * 重視ポイントの選択肢
     */
    public const PRIORITY_POINT_OPTIONS = [
        'safety' => '安全第一',
        'punctuality' => '時間厳守',
        'efficiency' => '効率性重視',
        'detailed_explanation' => '丁寧な説明重視',
        'enjoyable_conversation' => '楽しい会話重視',
        'respect_autonomy' => '自主性の尊重重視',
        'flexible_response' => '柔軟で臨機応変な対応重視',
    ];

    /**
     * 重視ポイントのラベルを取得
     */
    public function getPriorityPointLabels(): array
    {
        $profile = $this->isGuide() ? $this->guideProfile : $this->userProfile;
        if (!$profile || !$profile->priority_points) {
            return [];
        }
        
        $points = is_array($profile->priority_points) 
            ? $profile->priority_points 
            : json_decode($profile->priority_points, true) ?? [];
        
        $labels = [];
        foreach ($points as $key) {
            if (isset(self::PRIORITY_POINT_OPTIONS[$key])) {
                $labels[] = self::PRIORITY_POINT_OPTIONS[$key];
            }
        }
        
        // その他がある場合
        if (!empty($profile->priority_points_other)) {
            $labels[] = 'その他: ' . $profile->priority_points_other;
        }
        
        return $labels;
    }
}

