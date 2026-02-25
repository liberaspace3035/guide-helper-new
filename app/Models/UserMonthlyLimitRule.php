<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMonthlyLimitRule extends Model
{
    protected $table = 'user_monthly_limit_rules';

    protected $fillable = [
        'user_id',
        'request_type',
        'effective_from',
        'limit_hours',
    ];

    protected $casts = [
        'limit_hours' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
