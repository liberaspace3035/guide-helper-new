<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMonthlyLimit extends Model
{
    use HasFactory;

    protected $table = 'user_monthly_limits';

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'request_type',
        'limit_hours',
        'used_hours',
    ];

    protected $casts = [
        'limit_hours' => 'decimal:2',
        'used_hours' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 指定月の残時間を取得
     */
    public function getRemainingHoursAttribute(): float
    {
        return max(0, $this->limit_hours - $this->used_hours);
    }
}




