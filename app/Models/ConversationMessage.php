<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id', 'role', 'content',
        'model_used', 'provider', 'tokens_input', 'tokens_output', 'cost_inr_paise',
        'summarised_into_context', 'created_at',
    ];

    protected $casts = [
        'summarised_into_context' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
