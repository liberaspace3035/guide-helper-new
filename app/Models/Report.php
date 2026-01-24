<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'matching_id',
        'request_id',
        'guide_id',
        'user_id',
        'service_content',
        'report_content',
        'actual_date',
        'actual_start_time',
        'actual_end_time',
        'status',
        'revision_notes',
        'submitted_at',
        'user_approved_at',
        'admin_approved_at',
        'approved_at',
    ];

    protected $casts = [
        'actual_date' => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'submitted_at' => 'datetime',
        'user_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function matching()
    {
        return $this->belongsTo(Matching::class);
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

