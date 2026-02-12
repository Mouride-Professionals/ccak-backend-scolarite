<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'title',
        'content',
        'priority',
        'target_audience',
        'publish_at',
        'expire_at',
        'is_draft',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
        'is_draft' => 'boolean',
    ];

    // Enums
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function dismissedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user')
            ->withTimestamps()
            ->withPivot('dismissed_at');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_draft', false)
            ->where(function ($q) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('target_audience')
                ->orWhereJsonContains('target_audience->roles', $user->role)
                ->orWhereJsonContains('target_audience->user_ids', $user->id);
        });
    }

    public function scopeNotDismissedBy($query, string $userId)
    {
        return $query->whereDoesntHave('dismissedBy', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $sub) use ($term, $likeOperator): void {
            $sub->where('title', $likeOperator, "%{$term}%")
                ->orWhere('content', $likeOperator, "%{$term}%");
        });
    }

    // Accessors
    public function getTargetFiltersAttribute(): array
    {
        $audience = $this->target_audience ?? [];

        return [
            'roles' => $audience['roles'] ?? [],
            'classes' => $audience['classes'] ?? [],
            'user_ids' => $audience['user_ids'] ?? [],
        ];
    }

    // Methods
    public function publish(): bool
    {
        return $this->update([
            'is_draft' => false,
            'publish_at' => $this->publish_at ?? now(),
        ]);
    }

    public function dismissFor(string $userId): void
    {
        $this->dismissedBy()->attach($userId, [
            'dismissed_at' => now(),
        ]);
    }
}
