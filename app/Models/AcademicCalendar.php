<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendar extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'academic_year_id',
        'start_date',
        'end_date',
        'working_days',
        'weekend_days',
        'hour_slots',
        'break_slots',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'working_days' => 'array',
        'weekend_days' => 'array',
        'hour_slots' => 'array',
        'break_slots' => 'array',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
