<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPipelineStep extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pipeline_run_id', 'step_type', 'model_used',
        'prompt', 'raw_output', 'parsed_output', 'human_edits',
        'tokens_input', 'tokens_output', 'cost_inr_paise', 'duration_ms',
        'status', 'error_message', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PostPipelineRun::class, 'pipeline_run_id');
    }
}
