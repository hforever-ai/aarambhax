<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    /** @var string No updated_at column — usage logs are append-only. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action_type',
        'tier',
        'model_used',
        'gemini_calls',
        'cost_inr_paise',
        'paid_equivalent_paise',
        'tokens_in',
        'tokens_out',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'gemini_calls' => 'integer',
        'cost_inr_paise' => 'integer',
        'paid_equivalent_paise' => 'integer',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'reference_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
