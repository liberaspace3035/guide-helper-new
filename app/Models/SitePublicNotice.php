<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePublicNotice extends Model
{
    protected $table = 'site_public_notices';

    public const CATEGORY_SERVICE_AREA = 'service_area';
    public const CATEGORY_MAINTENANCE = 'maintenance';
    public const CATEGORY_EVENT = 'event';
    public const CATEGORY_POLICY = 'policy';
    public const CATEGORY_GUIDE_RECRUIT = 'guide_recruit';
    public const CATEGORY_MEDIA = 'media';

    public const CATEGORIES = [
        self::CATEGORY_SERVICE_AREA => 'サービス提供エリア',
        self::CATEGORY_MAINTENANCE => 'メンテナンス',
        self::CATEGORY_EVENT => 'イベント',
        self::CATEGORY_POLICY => '制度変更',
        self::CATEGORY_GUIDE_RECRUIT => 'ガイド募集',
        self::CATEGORY_MEDIA => 'メディア掲載',
    ];

    protected $fillable = [
        'category',
        'title',
        'body',
        'detail_url',
        'published_at',
        'is_visible',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
