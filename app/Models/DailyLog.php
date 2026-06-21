<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $fillable = [
        'user_id', 'log_date', 'studied_topics',
        'hours_studied', 'mood', 'food', 'expenses',
    ];

    protected $casts = [
        'log_date'      => 'date',
        'hours_studied' => 'float',
        'expenses'      => 'float',
        'mood'          => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moodEmoji(): string
    {
        return match ($this->mood) {
            1 => '😫', 2 => '😐', 3 => '🙂', 4 => '😊', 5 => '🔥',
            default => '—',
        };
    }
}
