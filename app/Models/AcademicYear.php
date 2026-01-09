<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-set is_current: ensure only one year is current at a time
        static::saving(function ($academicYear) {
            if ($academicYear->is_current) {
                static::where('id', '!=', $academicYear->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }

    // Scope for current year
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeIsActive(Builder $query, mixed $value = true): Builder
    {
        $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isActive === null) {
            return $query;
        }

        return $query->where('is_active', $isActive);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where('name', 'ilike', "%{$term}%");
    }

    public function getPrerequisitesAttribute($value): array
    {
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    // HasMany enrollments
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // HasMany course enrollments
    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    // Get current academic year
    public static function getCurrentYear()
    {
        return static::current()->first();
    }

    // Validation rules
    public static function validationRules($id = null): array
    {
        return [
            'name' => 'required|string|max:255|unique:academic_years,name,' . $id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Check if date is within this academic year
    public function isDateInRange($date): bool
    {
        $checkDate = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        return $checkDate->between($this->start_date, $this->end_date);
    }
}
