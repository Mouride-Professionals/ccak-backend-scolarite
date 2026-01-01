<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'documents';

    protected $fillable = [
        'student_id',
        'type',
        'file_path',
        'file_name',
        'status',
        'reviewed_by',
        'notes',
        'metadata',
        'uploaded_at',
        'reviewed_at',
    ];

    protected $casts = [
        'student_id' => 'string',
        'reviewed_by' => 'string',
        'type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'file_path' => 'string',
        'file_name' => 'string',
        'notes' => 'string',
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => DocumentStatus::PENDING,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Accesseurs
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type?->label() ?? 'Inconnu'
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->label() ?? 'Inconnu'
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->color() ?? 'secondary'
        );
    }

    public function isPending(): bool
    {
        return $this->status === DocumentStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === DocumentStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === DocumentStatus::REJECTED;
    }

    protected function fileSizeHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!isset($this->metadata['size'])) {
                    return 'N/A';
                }

                $size = $this->metadata['size'];
                $units = ['B', 'KB', 'MB', 'GB'];

                for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
                    $size /= 1024;
                }

                return round($size, 2) . ' ' . $units[$i];
            }
        );
    }

    /**
     * Scopes
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::REJECTED);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForStudent(Builder $query, string $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::PENDING)
            ->whereNull('reviewed_at');
    }

    public function scopeUploadedBetween(Builder $query, mixed $value): Builder
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
            $query->whereDate('uploaded_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('uploaded_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($term): void {
            $sub->where('file_name', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");
        });
    }
       /**
     * Approve the document.
     */
    public function approve(Admin $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => DocumentStatus::APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Reject the document.
     */
    public function reject(Admin $admin, string $notes): void
    {
        $this->update([
            'status' => DocumentStatus::REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get document type labels.
     */
    public static function typeLabels(): array
    {
        $labels = [];

        foreach (DocumentType::cases() as $type) {
            $labels[$type->value] = $type->label();
        }

        return $labels;
    }
}
