<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaryaMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'karya_id', 'role', 'content', 'intent',
        'model_used', 'tokens_input', 'tokens_output',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }
}
