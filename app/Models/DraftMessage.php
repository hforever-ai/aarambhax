<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'draft_id', 'role', 'content',
        'draft_snapshot', 'draft_diff_from_previous',
        'target_section_id', 'selection_start', 'selection_end',
        'intent', 'model_used', 'tokens_input', 'tokens_output', 'cost_inr_paise',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }
}
