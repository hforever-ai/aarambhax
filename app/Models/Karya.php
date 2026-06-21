<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karya extends Model
{
    protected $fillable = [
        'case_id', 'user_id',
        'type', 'title', 'language',
        'input_document_ids', 'parameters',
        'pipeline_status', 'pipeline_stage', 'pipeline_progress',
        'pipeline_started_at', 'pipeline_finished_at', 'pipeline_error',
        'output_markdown', 'output_json',
        'model_used', 'tier', 'tokens_in', 'tokens_out',
        'cost_inr_paise', 'paid_equivalent_paise', 'pii_redactions',
    ];

    protected $casts = [
        'input_document_ids' => 'array',
        'parameters' => 'array',
        'output_json' => 'array',
        'pipeline_started_at' => 'datetime',
        'pipeline_finished_at' => 'datetime',
        'pipeline_progress' => 'integer',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'cost_inr_paise' => 'integer',
        'paid_equivalent_paise' => 'integer',
        'pii_redactions' => 'integer',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(KaryaMessage::class)->orderBy('created_at');
    }

    public function isPipelineComplete(): bool
    {
        return in_array($this->pipeline_status, ['done', 'failed'], true);
    }

    /**
     * Resolve the Document records from input_document_ids — used to build
     * the doc_pack at run time.
     */
    public function inputDocuments()
    {
        return Document::whereIn('id', $this->input_document_ids ?? [])->get();
    }
}
