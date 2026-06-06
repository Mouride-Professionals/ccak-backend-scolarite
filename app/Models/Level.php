<?php

namespace App\Models;

use App\Enums\LevelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Level extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
        'type',
        'degree_cycle_id',
        'numero',
        'duration_semesters',
        'is_active',
        'synced_from',
        'last_synced_at',
    ];

    protected $casts = [
        'type' => LevelType::class,
        'is_active' => 'boolean',
    ];

    public function degreeCycle(): BelongsTo
    {
        return $this->belongsTo(DegreeCycle::class, 'degree_cycle_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'level_id');
    }

    // level_id now lives on enrollments, not students
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            Enrollment::class,
            'level_id',   // FK on Enrollment pointing to Level
            'id',         // FK on Student (its PK)
            'id',         // local key on Level
            'student_id'  // key on Enrollment pointing to Student
        );
    }
}
