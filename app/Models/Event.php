<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';

    /** 公開カテゴリ（管理者が変更可能・登録時に選択） */
    public const CATEGORY_OUTING_EXPERIENCE = 'outing_experience';
    public const CATEGORY_VISUAL_CHILDREN = 'visual_children';
    public const CATEGORY_LEARNING = 'learning';
    public const CATEGORY_WORK_CAREER = 'work_career';
    public const CATEGORY_CONNECTION = 'connection';
    public const CATEGORY_NOTICE_OTHER = 'notice_other';

    public const CATEGORIES = [
        self::CATEGORY_OUTING_EXPERIENCE => 'おでかけ・体験',
        self::CATEGORY_VISUAL_CHILDREN => '視覚障害児参加可能',
        self::CATEGORY_LEARNING => '学び・スキルアップ',
        self::CATEGORY_WORK_CAREER => '仕事・キャリア',
        self::CATEGORY_CONNECTION => 'つながり・交流',
        self::CATEGORY_NOTICE_OTHER => 'お知らせ・募集・その他',
    ];

    protected $fillable = [
        'title',
        'category',
        'prefecture',
        'place',
        'start_at',
        'end_at',
        'url',
        'description',
        'created_by',
        'submitter_name',
        'submitter_email',
        'email_verified_at',
        'verification_token',
        'verification_token_expires_at',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'verification_token_expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function personalCalendarEntries(): HasMany
    {
        return $this->hasMany(PersonalCalendarEntry::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now()->startOfDay());
    }

    public function scopePast($query)
    {
        return $query->where('start_at', '<', now()->startOfDay());
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function isPast(): bool
    {
        if (!$this->start_at) {
            return false;
        }

        return $this->start_at->lt(now()->startOfDay());
    }
}
