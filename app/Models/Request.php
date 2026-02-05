<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'nominated_guide_id',
        'request_type',
        'prefecture',
        'destination_address',
        'masked_address',
        'meeting_place',
        'service_content',
        'request_date',
        'request_time',
        'start_time',
        'end_time',
        'duration',
        'notes',
        'formatted_notes',
        'guide_gender',
        'guide_age',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
        // request_time, start_time, end_timeはTIME型なのでキャストしない（文字列として扱う）
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guideAcceptances()
    {
        return $this->hasMany(GuideAcceptance::class);
    }

    public function matching()
    {
        return $this->hasOne(Matching::class);
    }

    public function report()
    {
        return $this->hasOne(Report::class);
    }
}

