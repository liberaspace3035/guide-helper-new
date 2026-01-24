<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matching extends Model
{
    use HasFactory;

    protected $table = 'matchings';

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'user_id',
        'guide_id',
        'matched_at',
        'completed_at',
        'report_completed_at',
        'status',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'completed_at' => 'datetime',
        'report_completed_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function report()
    {
        return $this->hasOne(Report::class);
    }
}

