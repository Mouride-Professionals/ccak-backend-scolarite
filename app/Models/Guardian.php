<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'full_name',
        'relationship',
        'phone',
        'phone_2',
        'email',
        'address',
        'occupation',
    ];

    /**
     * Get the student that owns the guardian.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope a query to only include fathers.
     */
    public function scopeFathers($query)
    {
        return $query->where('relationship', 'FATHER');
    }

    /**
     * Scope a query to only include mothers.
     */
    public function scopeMothers($query)
    {
        return $query->where('relationship', 'MOTHER');
    }

    /**
     * Scope a query to only include guardians (non-parents).
     */
    public function scopeGuardians($query)
    {
        return $query->where('relationship', 'GUARDIAN');
    }
}
