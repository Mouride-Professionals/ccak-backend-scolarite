<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegistrationStatus;
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

    protected $fillable = [
        'id',
        'student_id',
        'academic_program_id',
        'academic_year_id',
        'level_id',
        'current_semester',
        'status',
        'enrollment_date',
        'registration_fee_paid',
        'registration_number',
        'notes',
        'is_repeating',
        'is_medically_fit',
        'is_scholarship_holder',
        'scholarship_type',
        'scholarship_amount',
        'is_registered_elsewhere',
        'is_willing_to_cancel_other_registration',
        'certification_file_url',
        'exam_number',
        'synced_from',
        'last_synced_at',
    ];

    protected $casts = [
        'student_id' => 'string',
        'academic_program_id' => 'string',
        'academic_year_id' => 'string',
        'level_id' => 'string',
        'current_semester' => 'integer',
        'status' => RegistrationStatus::class,
        'enrollment_date' => 'date',
        'registration_fee_paid' => 'float',
        'scholarship_amount' => 'decimal:2',
        'is_repeating' => 'boolean',
        'is_medically_fit' => 'boolean',
        'is_scholarship_holder' => 'boolean',
        'is_registered_elsewhere' => 'boolean',
        'is_willing_to_cancel_other_registration' => 'boolean',
        'exam_number' => 'string',
        'last_synced_at' => 'datetime',
    ];

    public static function generateExamNumber(string $yearCode, int $sequence): string
    {
        return sprintf('AK-%s-%04d', $yearCode, $sequence);
    }

    public static function getStatuses(): array
    {
        return RegistrationStatus::values();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    /** @return BelongsTo<AcademicYear, Enrollment> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
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
            'status' => 'required|in:'.implode(',', self::getStatuses()),
            'enrollment_date' => 'required|date',
            'registration_fee_paid' => 'required|numeric|min:0',
            'is_scholarship_holder' => 'boolean',
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

    public function isActive(): bool
    {
        return $this->status === RegistrationStatus::VALIDATED;
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
