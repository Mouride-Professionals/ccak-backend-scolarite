<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEnrollment extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $table = 'course_enrollments';

    protected $fillable = ['student_id','enrollment_id', 'course_id', 'academic_year_id', 'semester', 'status', 'enrollment_date', 'drop_date'];

    protected $casts = [
        'student_id' => 'string',
        'enrollment_id' => 'string',
        'course_id' => 'string',
        'academic_year_id' => 'string',
        'semester' => 'integer',
        'status' => 'string',
        'enrollment_date' => 'date',
        'drop_date' => 'date',
    ];

    // Status enum constants
    const STATUS_ENROLLED = 'ENROLLED';
    const STATUS_DROPPED = 'DROPPED';
    const STATUS_COMPLETED = 'COMPLETED';

    // Get all valid statuses
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ENROLLED,
            self::STATUS_DROPPED,
            self::STATUS_COMPLETED,
        ];
    }

    // BelongsTo relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    // Validation rules
    public static function validationRules($id = null): array
    {
        return [
            'enrollment_id' => 'required|uuid|exists:enrollments,id',
            'course_id' => 'required|uuid|exists:courses,id',
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|min:1|max:12',
            'status' => 'required|in:' . implode(',', self::getStatuses()),
            'enrollment_date' => 'required|date',
            'drop_date' => 'nullable|date|after:enrollment_date',
        ];
    }

    // Check if unique constraint is satisfied
    public static function isDuplicate($enrollmentId, $courseId, $yearId, $excludeId = null): bool
    {
        $query = static::where('enrollment_id', $enrollmentId)
            ->where('course_id', $courseId)
            ->where('academic_year_id', $yearId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Check if course enrollment is active
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    // Check prerequisites for a course
    public static function checkPrerequisites($studentId, $courseId): array
    {
        $course = Course::find($courseId);

        if (!$course || empty($course->prerequisites)) {
            return ['satisfied' => true, 'missing' => []];
        }

        $prerequisites = is_array($course->prerequisites)
            ? $course->prerequisites
            : json_decode($course->prerequisites, true);

        if (empty($prerequisites)) {
            return ['satisfied' => true, 'missing' => []];
        }

        // Get all completed courses for this student
        $completedCourses = static::whereHas('enrollment', function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where('status', self::STATUS_COMPLETED)
            ->pluck('course_id')
            ->toArray();

        $missingPrerequisites = array_diff($prerequisites, $completedCourses);

        return [
            'satisfied' => empty($missingPrerequisites),
            'missing' => $missingPrerequisites,
        ];
    }

    // Check seat availability for a course
    public static function checkSeatAvailability($courseId, $academicYearId, $semester): array
    {
        // Count current enrollments for this course
        $currentEnrollments = static::where('course_id', $courseId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', self::STATUS_ENROLLED)
            ->count();

        // Get course max capacity (assuming Course model has a max_students field)
        $course = Course::find($courseId);
        $maxCapacity = $course->max_students ?? 50; // Default to 50 if not set

        return [
            'available' => $currentEnrollments < $maxCapacity,
            'current' => $currentEnrollments,
            'max' => $maxCapacity,
            'remaining' => max(0, $maxCapacity - $currentEnrollments),
        ];
    }

    public function scopeEnrollmentDateBetween(Builder $query, mixed $value): Builder
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
            $query->whereDate('enrollment_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('enrollment_date', '<=', $to);
        }

        return $query;
    }

    public function scopeDropDateBetween(Builder $query, mixed $value): Builder
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
            $query->whereDate('drop_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('drop_date', '<=', $to);
        }

        return $query;
    }
}
