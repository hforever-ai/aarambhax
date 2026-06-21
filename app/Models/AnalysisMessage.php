<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'analysis_id', 'role', 'content',
        'analysis_snapshot', 'analysis_diff_from_previous',
        'target_section_id', 'selection_start', 'selection_end',
        'intent', 'model_used', 'tokens_input', 'tokens_output', 'cost_inr_paise',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(CaseAnalysis::class, 'analysis_id');
    }
}
