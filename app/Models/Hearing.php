<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hearing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'case_id', 'date', 'time', 'court', 'purpose',
        'outcome', 'next_date', 'reminded_at',
    ];

    protected $casts = [
        'date' => 'date',
        'next_date' => 'date',
        'reminded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }
}
