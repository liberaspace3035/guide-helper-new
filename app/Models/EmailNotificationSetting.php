<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailNotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'email_notification_settings';

    protected $fillable = [
        'notification_type',
        'is_enabled',
        'reminder_days',
        'scheduled_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'reminder_days' => 'integer',
    ];
}




