<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use App\Models\Enums\GradeStatus;
use App\Models\Enums\GradeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Grade extends Model implements AuditableContract
{
    use HasFactory;
    use UsesUuidV7;
    use Auditable;

    protected $table = 'grades';

    protected $fillable = ['course_enrollment_id', 'student_id', 'course_id', 'type', 'score', 'max_score', 'weight', 'entered_by', 'status', 'entered_at', 'validated_at'];

    protected $casts = [
        'course_enrollment_id' => 'string',
        'student_id' => 'string',
        'course_id' => 'string',
        'type' => GradeType::class,
        'score' => 'float',
        'max_score' => 'float',
        'weight' => 'float',
        'entered_by' => 'string',
        'status' => GradeStatus::class,
        'entered_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public array $auditEvents = ['created', 'updated', 'deleted'];
    public array $auditExclude = ['created_at', 'updated_at'];

    public function courseEnrollment()
    {
        return $this->belongsTo(\App\Models\CourseEnrollment::class, 'course_enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(\App\Models\Course::class, 'course_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'entered_by');
    }

    public function scopeEnteredBetween(Builder $query, mixed $value): Builder
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
            $query->whereDate('entered_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('entered_at', '<=', $to);
        }

        return $query;
    }

    public function scopeValidatedBetween(Builder $query, mixed $value): Builder
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
            $query->whereDate('validated_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('validated_at', '<=', $to);
        }

        return $query;
    }
}
