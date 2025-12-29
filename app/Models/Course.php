<?php

namespace App\Models;

use App\Models\Concerns\AuditableWithUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_unit_id',
        'code',
        'name',
        'description',
        'credits',
        'hours_lecture',
        'hours_td',
        'hours_tp',
        'coefficient',
        'prerequisites',
        'is_active',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'is_active' => 'boolean',
    ];

    // Audit configuration
    public $auditEvents = ['created', 'updated', 'deleted'];
    public $auditExclude = ['created_at', 'updated_at'];

    public function courseUnit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getPrerequisitesAttribute($value): array
    {
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    public function enrollments()
    {
        return $this->hasMany(\App\Models\CourseEnrollment::class, 'course_id');
    }

    public function grades()
    {
        return $this->hasMany(\App\Models\Grade::class, 'course_id');
    }

    public function students()
    {
        return $this->belongsToMany(\App\Models\Student::class, 'course_enrollments', 'course_id', 'student_id');
    }
    /**
     * Handle prerequisites for audit
     */
    public function setPrerequisitesAttribute($value): void
    {
        $this->attributes['prerequisites'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

    /**
     * Transform audit data for prerequisites
     */
    public function transformAudit(array $data): array
    {
        $data = parent::transformAudit($data);

        // Format prerequisites for audit display
        foreach (['old_values', 'new_values'] as $key) {
            if (isset($data[$key]['prerequisites'])) {
                $prerequisites = $data[$key]['prerequisites'];
                if (is_string($prerequisites) && json_decode($prerequisites)) {
                    $data[$key]['prerequisites'] = json_decode($prerequisites, true);
                }
            }
        }

        return $data;
    }

    // HasMany course enrollments
    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    // Get current enrollment count
    public function getCurrentEnrollmentCount($academicYearId, $semester): int
    {
        return $this->courseEnrollments()
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', CourseEnrollment::STATUS_ENROLLED)
            ->count();
    }
}
