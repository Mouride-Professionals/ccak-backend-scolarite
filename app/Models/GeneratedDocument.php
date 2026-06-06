<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $student_id
 * @property string $type
 * @property string $document_number
 * @property string $file_path
 * @property string $generated_by
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $issued_at
 * @property string $status
 */
class GeneratedDocument extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $table = 'generated_documents';

    protected $fillable = [
        'student_id',
        'type',
        'document_number',
        'file_path',
        'media_id',
        'generated_by',
        'metadata',
        'generated_at',
        'issued_at',
        'status',
    ];

    protected $casts = [
        'student_id' => 'string',
        'document_number' => 'string',
        'file_path' => 'string',
        'media_id' => 'integer',
        'generated_by' => 'string',
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public const TYPE_TRANSCRIPT = 'TRANSCRIPT';

    public const TYPE_CERTIFICATE = 'CERTIFICATE';

    public const TYPE_ATTESTATION = 'ATTESTATION';

    public const TYPE_ID_CARD = 'ID_CARD';

    public const TYPE_DIPLOMA = 'DIPLOMA';

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_REVOKED = 'REVOKED';

    public static function getTypes(): array
    {
        return [
            self::TYPE_TRANSCRIPT,
            self::TYPE_CERTIFICATE,
            self::TYPE_ATTESTATION,
            self::TYPE_ID_CARD,
            self::TYPE_DIPLOMA,
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ISSUED,
            self::STATUS_REVOKED,
        ];
    }

    // Scopes
    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeFilterByStudent(Builder $query, ?string $studentId): Builder
    {
        return $studentId ? $query->where('student_id', $studentId) : $query;
    }

    public function scopeFilterByDateRange(Builder $query, mixed $value): Builder
    {
        $from = null;
        $to = null;

        if (is_array($value)) {
            $from = $value['from'] ?? $value[0] ?? null;
            $to = $value['to'] ?? $value[1] ?? null;
        } elseif (is_string($value) && str_contains($value, ',')) {
            [$from, $to] = array_map('trim', explode(',', $value, 2));
        } elseif (is_string($value)) {
            $from = $value;
        }

        if ($from) {
            $query->whereDate('generated_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('generated_at', '<=', $to);
        }

        return $query;
    }

    public function scopeGeneratedBetween(Builder $query, mixed $value): Builder
    {
        return $this->scopeFilterByDateRange($query, $value);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where('document_number', $likeOperator, "%{$term}%");
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REVOKED);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('official_documents')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }
}
