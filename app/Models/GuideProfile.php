<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideProfile extends Model
{
    use HasFactory;

    protected $table = 'guide_profiles';

    protected $fillable = [
        'user_id',
        'introduction',
        'available_areas',
        'available_days',
        'available_times',
        'employee_number',
        'application_reason',
        'goal',
        'qualifications',
        'preferred_work_hours',
    ];

    protected $casts = [
        'available_areas' => 'array',
        'available_days' => 'array',
        'available_times' => 'array',
        'qualifications' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}



