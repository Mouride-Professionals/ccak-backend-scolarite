<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, UsesUuidV7;

    public const STATUS_PRESENT = 'PRESENT';

    public const STATUS_ABSENT = 'ABSENT';

    public const STATUS_LATE = 'LATE';

    public const STATUS_EXCUSED = 'EXCUSED';

    protected $fillable = [
        'course_log_id',
        'student_id',
        'status',
        'marked_at',
        'notes',
        'absence_count',
        'is_dispensed',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'absence_count' => 'integer',
        'is_dispensed' => 'boolean',
    ];

    public function courseLog(): BelongsTo
    {
        return $this->belongsTo(CourseLog::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
