<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideProposal extends Model
{
    protected $table = 'guide_proposals';

    protected $fillable = [
        'guide_id',
        'user_id',
        'request_type',
        'proposed_date',
        'start_time',
        'end_time',
        'service_content',
        'message',
        'prefecture',
        'destination_address',
        'meeting_place',
        'status',
    ];

    protected $casts = [
        'proposed_date' => 'date',
    ];

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
