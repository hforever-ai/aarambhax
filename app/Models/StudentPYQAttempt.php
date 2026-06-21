<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPYQAttempt extends Model
{
    protected $table = 'student_pyq_attempts';

    protected $fillable = ['user_id', 'pyq_id', 'note_id', 'status'];

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function pyq(): BelongsTo   { return $this->belongsTo(PYQQuestion::class, 'pyq_id'); }
    public function note(): BelongsTo  { return $this->belongsTo(StudentNote::class, 'note_id'); }
}
