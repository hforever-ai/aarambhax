<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'analysis_id', 'content_md', 'context_snapshot',
        'created_by', 'message_id', 'label', 'created_at',
    ];

    protected $casts = [
        'context_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(CaseAnalysis::class, 'analysis_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AnalysisMessage::class, 'message_id');
    }
}
