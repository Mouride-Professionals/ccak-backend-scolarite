<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'exam_session_id',
        'course_id',
        'room_id',
        'date',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'exam_session_id' => 'string',
        'course_id' => 'string',
        'room_id' => 'string',
        'date' => 'date',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'exam_schedule_id');
    }

    public function invigilators(): BelongsToMany
    {
        return $this->belongsToMany(
            FacultyMember::class,
            'exam_schedule_invigilators',
            'exam_schedule_id',
            'faculty_member_id'
        );
    }
}
