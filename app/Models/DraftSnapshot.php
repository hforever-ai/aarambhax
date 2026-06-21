<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'draft_id', 'content_md', 'context_snapshot', 'created_by',
        'message_id', 'label', 'created_at',
    ];

    protected $casts = [
        'context_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }
}
