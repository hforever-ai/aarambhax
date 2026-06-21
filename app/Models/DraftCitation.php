<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftCitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_id', 'citation_type', 'raw_text',
        'statute_code', 'section_no', 'judgment_id',
        'position_in_draft', 'verification_status',
        'added_in_message_id', 'removed_in_message_id',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }
}
