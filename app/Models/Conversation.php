<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    public const KIND_BRAINSTORM = 'brainstorm';
    public const KIND_BAHAS = 'bahas';
    public const KIND_CROSS_EXAM = 'cross_exam';
    public const KIND_SAMJHAO = 'samjhao';
    public const KIND_ANALYSIS = 'analysis';
    public const KIND_DRAFT = 'draft';
    public const KIND_NOTES = 'notes';

    public const KINDS = [
        self::KIND_BRAINSTORM,
        self::KIND_BAHAS,
        self::KIND_CROSS_EXAM,
        self::KIND_SAMJHAO,
        self::KIND_ANALYSIS,
        self::KIND_DRAFT,
        self::KIND_NOTES,
    ];

    protected $fillable = [
        'case_id', 'user_id', 'kind', 'title', 'language', 'status',
        'context_summary_md', 'total_tokens_in', 'total_tokens_out',
        'source_analysis_id', 'converted_draft_id',
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
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    /**
     * Only messages still in the active context window — not yet folded into
     * context_summary_md by the rolling-summary process.
     */
    public function activeMessages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)
            ->where('summarised_into_context', false)
            ->orderBy('created_at');
    }

    public function sourceAnalysis(): BelongsTo
    {
        return $this->belongsTo(CaseAnalysis::class, 'source_analysis_id');
    }

    public function convertedDraft(): BelongsTo
    {
        return $this->belongsTo(Draft::class, 'converted_draft_id');
    }
}
