<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRecord extends Model
{
    use HasFactory;

    protected $table = 'case_records';

    protected $fillable = [
        'user_id', 'client_id', 'title', 'forum', 'court_name', 'case_no',
        'jurisdiction', 'category', 'status', 'opposing_party',
        'khasra_no', 'khata_no', 'khatauni_no', 'gram', 'tehsil', 'jila',
        'state_json',
    ];

    protected $casts = [
        'state_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class, 'case_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(CaseAnalysis::class, 'case_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'case_id');
    }

    public function karyas(): HasMany
    {
        return $this->hasMany(Karya::class, 'case_id');
    }
}
