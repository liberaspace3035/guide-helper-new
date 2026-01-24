<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'contact_method',
        'notes',
        'recipient_number',
        'admin_comment',
        'introduction',
        'interview_date_1',
        'interview_date_2',
        'interview_date_3',
        'application_reason',
        'visual_disability_status',
        'disability_support_level',
        'daily_life_situation',
    ];

    protected $casts = [
        'interview_date_1' => 'datetime',
        'interview_date_2' => 'datetime',
        'interview_date_3' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}



