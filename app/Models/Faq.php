<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'language', 'translation_group_id',
        'question', 'answer', 'answer_html',
        'topic', 'related_statute_code', 'related_section_no',
        'display_order', 'is_featured', 'is_published',
        'helpful_count', 'not_helpful_count', 'view_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_faq');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true)->orderBy('display_order');
    }

    public function scopeLang(Builder $q, string $lang): Builder
    {
        return $q->where('language', $lang);
    }

    public function scopeTopic(Builder $q, string $topic): Builder
    {
        return $q->where('topic', $topic);
    }
}
