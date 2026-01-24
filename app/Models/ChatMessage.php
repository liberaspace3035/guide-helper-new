<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    public $timestamps = false;

    protected $fillable = [
        'matching_id',
        'sender_id',
        'message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function matching()
    {
        return $this->belongsTo(Matching::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}






