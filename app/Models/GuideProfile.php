<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideProfile extends Model
{
    use HasFactory;

    protected $table = 'guide_profiles';

    /**
     * 資格マスタ（選択式）
     * 外出支援（同行援護）に必要: dokoengogo_general, dokoengogo_advanced
     * 自宅支援に必要: kaigo_fukushishi, kaigo_jitsumushakenshu, kaigo_shoninshakenshu
     */
    public const QUALIFICATION_OPTIONS = [
        // 外出支援（同行援護）用の資格
        'dokoengogo_general' => '同行援護一般課程',
        'dokoengogo_advanced' => '同行援護応用課程',
        // 自宅支援用の資格
        'kaigo_fukushishi' => '介護福祉士',
        'kaigo_jitsumushakenshu' => '介護実務者研修',
        'kaigo_shoninshakenshu' => '介護初任者研修',
    ];

    /**
     * 外出支援に必要な資格キー
     */
    public const OUTING_REQUIRED_QUALIFICATIONS = [
        'dokoengogo_general',
        'dokoengogo_advanced',
    ];

    /**
     * 自宅支援に必要な資格キー
     */
    public const HOME_REQUIRED_QUALIFICATIONS = [
        'kaigo_fukushishi',
        'kaigo_jitsumushakenshu',
        'kaigo_shoninshakenshu',
    ];

    protected $fillable = [
        'user_id',
        'introduction',
        'priority_points',
        'priority_points_other',
        'available_areas',
        'available_days',
        'available_times',
        'employee_number',
        'admin_comment',
        'application_reason',
        'goal',
        'qualifications',
        'preferred_work_hours',
    ];

    protected $casts = [
        'available_areas' => 'array',
        'available_days' => 'array',
        'available_times' => 'array',
        'qualifications' => 'array',
        'priority_points' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 外出支援が可能かどうか
     */
    public function canSupportOuting(): bool
    {
        if (empty($this->qualifications)) {
            return false;
        }
        $qualKeys = $this->getQualificationKeys();
        return !empty(array_intersect($qualKeys, self::OUTING_REQUIRED_QUALIFICATIONS));
    }

    /**
     * 自宅支援が可能かどうか
     */
    public function canSupportHome(): bool
    {
        if (empty($this->qualifications)) {
            return false;
        }
        $qualKeys = $this->getQualificationKeys();
        return !empty(array_intersect($qualKeys, self::HOME_REQUIRED_QUALIFICATIONS));
    }

    /**
     * 資格キーの配列を取得（新形式・旧形式両対応）
     */
    public function getQualificationKeys(): array
    {
        if (empty($this->qualifications)) {
            return [];
        }

        $quals = is_array($this->qualifications) 
            ? $this->qualifications 
            : json_decode($this->qualifications, true) ?? [];

        // 新形式: ['dokoengogo_general', 'kaigo_fukushishi']
        // 旧形式: [{ name: '同行援護一般課程', obtained_date: '2024-01-01' }]
        $keys = [];
        foreach ($quals as $qual) {
            if (is_string($qual)) {
                // 新形式
                $keys[] = $qual;
            } elseif (is_array($qual) && isset($qual['key'])) {
                // 新形式（オブジェクト）
                $keys[] = $qual['key'];
            } elseif (is_array($qual) && isset($qual['name'])) {
                // 旧形式: 資格名から逆引き
                $name = $qual['name'];
                $foundKey = array_search($name, self::QUALIFICATION_OPTIONS);
                if ($foundKey !== false) {
                    $keys[] = $foundKey;
                }
            }
        }
        return array_unique($keys);
    }

    /**
     * 資格ラベルの配列を取得
     */
    public function getQualificationLabels(): array
    {
        $keys = $this->getQualificationKeys();
        $labels = [];
        foreach ($keys as $key) {
            if (isset(self::QUALIFICATION_OPTIONS[$key])) {
                $labels[] = self::QUALIFICATION_OPTIONS[$key];
            }
        }
        return $labels;
    }
}



