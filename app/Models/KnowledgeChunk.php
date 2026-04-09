<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'source_path',
        'title',
        'content',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}