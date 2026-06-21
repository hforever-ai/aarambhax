<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'case_id', 'user_id',
        'original_filename', 'stored_path', 'mime_type', 'bytes', 'content_sha256',
        'detected_doc_type', 'language', 'ocr_text', 'structured_fields',
        'ingest_model_used', 'ingest_tokens_in', 'ingest_tokens_out', 'ingest_confidence',
        'ingested_at',
    ];

    protected $casts = [
        'structured_fields' => 'array',
        'ingested_at' => 'datetime',
        'ingest_confidence' => 'float',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
