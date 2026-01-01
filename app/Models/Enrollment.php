<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $table = 'enrollments';

    protected $fillable = ['student_id', 'academic_program_id', 'academic_year_id', 'current_semester', 'status', 'enrollment_date', 'registration_fee_paid', 'is_scholarship'];

    protected $casts = [
        'student_id' => 'string',
        'academic_program_id' => 'string',
        'academic_year_id' => 'string',
        'current_semester' => 'integer',
        'status' => 'string',
        'enrollment_date' => 'date',
        'registration_fee_paid' => 'float',
        'is_scholarship' => 'boolean',
    ];

    // Status enum constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_REGISTERED = 'REGISTERED';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_WITHDRAWN = 'WITHDRAWN';

    // Get all valid statuses
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_REGISTERED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_WITHDRAWN,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    // HasMany course_enrollments
    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    // Active course enrollments
    public function activeCourseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class)
            ->where('status', CourseEnrollment::STATUS_ENROLLED);
    }

    // Validation rules
    public static function validationRules($id = null): array
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'academic_program_id' => 'required|uuid|exists:academic_programs,id',
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'current_semester' => 'required|integer|min:1|max:12',
            'status' => 'required|in:' . implode(',', self::getStatuses()),
            'enrollment_date' => 'required|date',
            'registration_fee_paid' => 'required|numeric|min:0',
            'is_scholarship' => 'boolean',
        ];
    }

    // Check if student is already enrolled
    public static function isDuplicate($studentId, $programId, $yearId, $excludeId = null): bool
    {
        $query = static::where('student_id', $studentId)
            ->where('academic_program_id', $programId)
            ->where('academic_year_id', $yearId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Check if enrollment is active
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_REGISTERED]);
    }

    // Get total enrolled courses count
    public function getTotalCoursesCount(): int
    {
        return $this->courseEnrollments()->count();
    }

    // Get active courses count
    public function getActiveCoursesCount(): int
    {
        return $this->activeCourseEnrollments()->count();
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
}
