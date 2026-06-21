<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'citation_type', 'raw_text',
        'statute_code', 'section_no', 'judgment_id', 'source_url',
        'verification_status', 'verified_at', 'verified_by', 'verifier_notes',
        'position_in_post',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
