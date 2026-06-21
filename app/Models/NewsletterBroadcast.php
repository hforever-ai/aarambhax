<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject', 'body_md', 'body_html', 'language_filter',
        'status', 'recipient_count', 'sent_count', 'failed_count', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
