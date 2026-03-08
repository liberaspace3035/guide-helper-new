<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $table = 'ratings';

    protected $fillable = [
        'report_id',
        'rater_id',
        'rated_id',
        'rater_type',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    /**
     * 評価スコアのラベル
     */
    public const SCORE_LABELS = [
        1 => '改善が必要',
        2 => '普通',
        3 => '良い',
    ];

    /**
     * 関連する報告書
     */
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * 評価者
     */
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * 評価された側
     */
    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    /**
     * スコアのラベルを取得
     */
    public function getScoreLabelAttribute(): string
    {
        return self::SCORE_LABELS[$this->score] ?? '不明';
    }
}
