<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseAnalysis extends Model
{
    protected $table = 'case_analyses';

    protected $fillable = [
        'case_id', 'user_id', 'title', 'language', 'analysis_type',
        'context_facts', 'context_legal', 'context_user_prefs',
        'current_content_md', 'current_content_html', 'status',
        'pipeline_status', 'pipeline_stage', 'pipeline_progress',
        'pipeline_started_at', 'pipeline_finished_at', 'pipeline_error',
    ];

    protected $casts = [
        'context_facts' => 'array',
        'context_legal' => 'array',
        'context_user_prefs' => 'array',
        'pipeline_started_at' => 'datetime',
        'pipeline_finished_at' => 'datetime',
        'pipeline_progress' => 'integer',
    ];

    public function isPipelineComplete(): bool
    {
        return in_array($this->pipeline_status, ['done', 'failed'], true);
    }

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
        return $this->hasMany(AnalysisMessage::class, 'analysis_id')->orderBy('created_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(AnalysisSnapshot::class, 'analysis_id')->orderByDesc('created_at');
    }
}
