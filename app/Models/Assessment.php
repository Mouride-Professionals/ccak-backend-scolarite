<?php

namespace App\Models;

use App\Enums\AssessmentType;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'course_id',
        'faculty_member_id',
        'academic_year_id',
        'title',
        'type',
        'date',
        'start_time',
        'duration_minutes',
        'room',
        'coefficient',
        'is_grades_published',
        'notes',
    ];

    protected $casts = [
        'id'                  => 'string',
        'course_id'           => 'string',
        'faculty_member_id'   => 'string',
        'academic_year_id'    => 'string',
        'type'                => AssessmentType::class,
        'date'                => 'date',
        'duration_minutes'    => 'integer',
        'coefficient'         => 'decimal:2',
        'is_grades_published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'faculty_member_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'assessment_id');
    }

    public function scopeForCourse(Builder $query, string $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeForAcademicYear(Builder $query, string $academicYearId): Builder
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeForFacultyMember(Builder $query, string $facultyMemberId): Builder
    {
        return $query->where('faculty_member_id', $facultyMemberId);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_grades_published', true);
    }
}
