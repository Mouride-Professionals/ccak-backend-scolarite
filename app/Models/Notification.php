<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'title',
        'message',
        'metadata',
        'is_read',
        'read_at',
        'sms_status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sms_status' => 'string',
    ];

    // Enums
    public const TYPE_GRADE_PUBLISHED = 'grade_published';
    public const TYPE_ENROLLMENT_CONFIRMED = 'enrollment_confirmed';
    public const TYPE_DOCUMENT_READY = 'document_ready';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_SYSTEM = 'system';

    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';

    public static function getTypes(): array
    {
        return [
            self::TYPE_GRADE_PUBLISHED,
            self::TYPE_ENROLLMENT_CONFIRMED,
            self::TYPE_DOCUMENT_READY,
            self::TYPE_PASSWORD_RESET,
            self::TYPE_WELCOME,
            self::TYPE_SYSTEM,
        ];
    }

    public static function getChannels(): array
    {
        return [
            self::CHANNEL_IN_APP,
            self::CHANNEL_EMAIL,
            self::CHANNEL_SMS,
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($term): void {
            $sub->where('title', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%");
        });
    }

    public function scopeIsRead(Builder $query, mixed $value = true): Builder
    {
        $isRead = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isRead === null) {
            return $query;
        }

        return $query->where('is_read', $isRead);
    }

    // Methods
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}
