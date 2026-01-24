<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideAcceptance extends Model
{
    use HasFactory;

    protected $table = 'guide_acceptances';

    protected $fillable = [
        'request_id',
        'guide_id',
        'status',
        'admin_decision',
        'user_selected',
    ];

    protected $casts = [
        'user_selected' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }
}

