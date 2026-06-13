<?php

namespace App\Models;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamSessionType;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'academic_year_id',
        'semester_number',
        'name',
        'type',
        'start_date',
        'end_date',
        'status',
        'use_exam_number',
    ];

    protected $casts = [
        'id' => 'string',
        'academic_year_id' => 'string',
        'semester_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => ExamSessionType::class,
        'status' => ExamSessionStatus::class,
        'use_exam_number' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'exam_session_id')->orderBy('date')->orderBy('start_time');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOp = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where('name', $likeOp, "%{$term}%");
    }
}
