<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'session_token',
        'messages',
    ];

    protected $casts = [
        'messages' => 'array',
    ];
}