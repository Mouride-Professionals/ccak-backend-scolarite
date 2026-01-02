<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'course_id',
        'faculty_member_id',
        'room_id',
        'activity_type_id',
        'academic_year_id',
        'semester_number',
        'day_of_week',
        'start_time',
        'end_time',
        'starts_on',
        'ends_on',
        'recurrence_pattern',
    ];

    protected $casts = [
        'semester_number' => 'integer',
        'day_of_week' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'recurrence_pattern' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function courseLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseLog::class);
    }
}
