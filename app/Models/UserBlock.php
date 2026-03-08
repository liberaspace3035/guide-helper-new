<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    use HasFactory;

    protected $table = 'user_blocks';

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'blocked_by_admin_id',
    ];

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    public function blockedByAdmin()
    {
        return $this->belongsTo(User::class, 'blocked_by_admin_id');
    }

    public function isAdminBlock(): bool
    {
        return $this->blocked_by_admin_id !== null;
    }
}
