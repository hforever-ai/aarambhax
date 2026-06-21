<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const ROLE_ADVOCATE = 'advocate';
    public const ROLE_STUDENT  = 'student';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'is_admin',
        'role',
        'approved_at',
        'approved_by_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isAdvocate(): bool
    {
        return $this->role === self::ROLE_ADVOCATE || $this->role === null;
    }

    /**
     * Filament panel access — only admins can reach /admin. Closes the
     * security gap where any authenticated user could previously access
     * the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
