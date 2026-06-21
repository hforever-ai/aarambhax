<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostPipelineRun extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'state', 'current_assignee_id', 'outline_json', 'notes'];

    protected $casts = [
        'outline_json' => 'array',
    ];

    public const STATES = [
        'idea',
        'outline_draft', 'outline_review', 'outline_approved',
        'draft_en', 'en_review', 'en_approved',
        'draft_hi', 'hi_review', 'both_approved',
        'assets_generating', 'assets_ready',
        'published', 'archived',
    ];

    public const REVIEW_STATES = ['outline_review', 'en_review', 'hi_review'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(PostPipelineStep::class, 'pipeline_run_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function isAwaitingReview(): bool
    {
        return in_array($this->state, self::REVIEW_STATES, true);
    }

    public function isPublished(): bool
    {
        return $this->state === 'published';
    }
}
