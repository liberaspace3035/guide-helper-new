<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'age',
        'gender',
        'address',
        'postal_code',
        'phone',
        'birth_date',
        'role',
        'is_allowed',
        'email_confirmed',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'email_confirmed' => 'boolean',
        'birth_date' => 'date',
    ];

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function guideProfile()
    {
        return $this->hasOne(GuideProfile::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function guideAcceptances()
    {
        return $this->hasMany(GuideAcceptance::class, 'guide_id');
    }

    public function matchingsAsUser()
    {
        return $this->hasMany(Matching::class, 'user_id');
    }

    public function matchingsAsGuide()
    {
        return $this->hasMany(Matching::class, 'guide_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function reportsAsGuide()
    {
        return $this->hasMany(Report::class, 'guide_id');
    }

    public function reportsAsUser()
    {
        return $this->hasMany(Report::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isGuide()
    {
        return $this->role === 'guide';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}

