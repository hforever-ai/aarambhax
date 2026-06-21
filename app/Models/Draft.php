<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Draft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'case_id', 'source_analysis_id', 'source_conversation_id',
        'title', 'forum', 'category', 'draft_type', 'language',
        'context_facts', 'context_legal', 'context_user_prefs',
        'current_content_md', 'current_content_html', 'status',
    ];

    protected $casts = [
        'context_facts' => 'array',
        'context_legal' => 'array',
        'context_user_prefs' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DraftMessage::class)->orderBy('created_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(DraftSnapshot::class)->orderByDesc('created_at');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(DraftCitation::class);
    }
}
