<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLog extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'schedule_id',
        'faculty_member_id',
        'created_by_user_id',
        'session_date',
        'topics',
        'chapters',
        'objectives',
        'notes',
        'signed_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'topics' => 'array',
        'chapters' => 'array',
        'objectives' => 'array',
        'signed_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
