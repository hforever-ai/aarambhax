<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name_en', 'name_hi', 'description_en', 'description_hi', 'display_order',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'hi' && $this->name_hi
            ? $this->name_hi
            : $this->name_en;
    }
}
