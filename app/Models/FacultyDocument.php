<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FacultyDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, UsesUuidV7;

    public const TYPE_CV = 'CV';

    public const TYPE_DIPLOMA = 'DIPLOMA';

    public const TYPE_CNI = 'CNI';

    public const TYPE_OTHER = 'OTHER';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'faculty_member_id',
        'type',
        'file_path',
        'file_name',
        'media_id',
        'status',
        'reviewed_by',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'media_id' => 'integer',
    ];

    public $auditEvents = ['created', 'updated', 'deleted'];

    public $auditExclude = ['created_at', 'updated_at'];

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function registerMediaCollections(): void
    {
        $pdfOnly = ['application/pdf'];
        $pdfOrImage = ['application/pdf', 'image/jpeg', 'image/png'];

        $this->addMediaCollection(self::TYPE_CV)
            ->singleFile()
            ->acceptsMimeTypes($pdfOnly);

        $this->addMediaCollection(self::TYPE_DIPLOMA)
            ->acceptsMimeTypes($pdfOnly);

        $this->addMediaCollection(self::TYPE_CNI)
            ->singleFile()
            ->acceptsMimeTypes($pdfOrImage);

        $this->addMediaCollection(self::TYPE_OTHER)
            ->acceptsMimeTypes($pdfOnly);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media?->collection_name !== self::TYPE_CNI) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->queued();
    }
}
