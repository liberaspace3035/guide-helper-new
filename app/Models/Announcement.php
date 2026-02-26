<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'target_audience',
        'reminder_enabled',
        'created_by',
    ];

    protected $casts = [
        'reminder_enabled' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }
}

