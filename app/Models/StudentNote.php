<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentNote extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'class_name', 'title', 'image_path', 'note_text',
        'ocr_text', 'organised_md', 'questions_json', 'polished_md', 'ai_status',
        'formula_sheet_md', 'flashcards_json', 'matched_topics',
        'youtube_url', 'source_type',
    ];

    protected $casts = [
        'matched_topics' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pyqAttempts(): HasMany
    {
        return $this->hasMany(\App\Models\StudentPYQAttempt::class, 'note_id');
    }
}
