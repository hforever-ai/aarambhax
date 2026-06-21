<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'language_pref', 'source', 'confirmed', 'unsubscribe_token'];

    protected $casts = ['confirmed' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            if (! $sub->unsubscribe_token) {
                $sub->unsubscribe_token = Str::random(40);
            }
        });
    }
}
