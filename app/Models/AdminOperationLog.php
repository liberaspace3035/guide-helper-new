<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminOperationLog extends Model
{
    use HasFactory;

    protected $table = 'admin_operation_logs';

    protected $fillable = [
        'admin_id',
        'operation_type',
        'target_type',
        'target_id',
        'operation_details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'operation_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

