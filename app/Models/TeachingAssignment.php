<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class TeachingAssignment extends Model
{
    use HasFactory, UsesUuidV7, SoftDeletes;

    public const ROLE_TITULAR = 'TITULAR';
    public const ROLE_TD = 'TD';
    public const ROLE_TP = 'TP';

    protected $fillable = [
        'faculty_member_id',
        'course_id',
        'academic_year_id',
        'role',
        'hours_assigned',
        'hourly_rate',
    ];

    protected $casts = [
        'hours_assigned' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public $auditEvents = ['created', 'updated', 'deleted'];
    public $auditExclude = ['created_at', 'updated_at', 'deleted_at'];

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($term): void {
            $sub->whereHas('course', function (Builder $course) use ($term): void {
                $course->where('name', 'ilike', "%{$term}%")
                    ->orWhere('code', 'ilike', "%{$term}%");
            })->orWhereHas('facultyMember', function (Builder $faculty) use ($term): void {
                $faculty->where('full_name', 'ilike', "%{$term}%")
                    ->orWhere('staff_number', 'ilike', "%{$term}%");
            });
        });
    }
}
