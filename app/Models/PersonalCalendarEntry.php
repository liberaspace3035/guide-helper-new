<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalCalendarEntry extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'title',
        'prefecture',
        'place',
        'start_at',
        'end_at',
        'url',
        'description',
        'reminder_30min_sent_at',
        'reminder_day_before_sent_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_30min_sent_at' => 'datetime',
        'reminder_day_before_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
