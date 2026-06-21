<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'language', 'translation_group_id', 'category_id', 'archetype',
        'title', 'subtitle', 'excerpt', 'body', 'body_html',
        'hero_image_url', 'hero_image_alt',
        'meta_title', 'meta_description', 'canonical_url', 'og_image_url',
        'author_id', 'status', 'published_at', 'scheduled_at',
        'reading_time_minutes', 'view_count',
        'has_downloadable_sample', 'sample_draft_url',
        'related_app_route',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'has_downloadable_sample' => 'boolean',
        'view_count' => 'integer',
        'reading_time_minutes' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class, 'post_faq')->orderBy('post_faq.display_order');
    }

    public function pipelineRuns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostPipelineRun::class);
    }

    public function currentPipelineRun(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PostPipelineRun::class, 'current_pipeline_run_id');
    }

    public function citations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostCitation::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeLang(Builder $q, string $lang): Builder
    {
        return $q->where('language', $lang);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getReadingTimeAttribute($value): int
    {
        if ($value) {
            return (int) $value;
        }
        $words = str_word_count(strip_tags($this->body ?? ''));
        return max(1, (int) ceil($words / 220));
    }

    public function getExcerptOrSummaryAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        $plain = strip_tags($this->body_html ?? $this->body ?? '');
        return Str::limit($plain, 200);
    }

    public function getCanonicalAttribute(): string
    {
        return $this->canonical_url ?: route('blog.show', $this->slug);
    }

    /**
     * Hero image with fallback. If the post has no specific hero set, return
     * the branded blog default SVG so cards/article pages always have an image.
     */
    public function getHeroImageOrDefaultAttribute(): string
    {
        if ($this->hero_image_url) {
            return $this->hero_image_url;
        }
        return asset('images/blog-default.svg');
    }
}
