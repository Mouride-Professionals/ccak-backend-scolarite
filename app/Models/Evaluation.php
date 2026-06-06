<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'course_id',
        'faculty_member_id',
        'academic_year_id',
        'start_date',
        'end_date',
        'question_template',
        'is_published',
        'response_deadline',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'question_template' => 'array',
        'is_published' => 'boolean',
        'response_deadline' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
