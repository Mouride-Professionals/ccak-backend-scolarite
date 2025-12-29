<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FacultyMember extends Model
{
    use HasFactory;

    protected $table = 'faculty_members';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'staff_number',
        'full_name',
        'phone',
        'address',
        'department_id',
        'rank',
        'contract_type',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Deliberation roles
    public function presidedSessions()
    {
        return $this->hasMany(DeliberationSession::class, 'presided_by');
    }

    public function jurySessions()
    {
        return $this->belongsToMany(
            DeliberationSession::class,
            'deliberation_session_jury',
            'faculty_member_id',
            'deliberation_session_id'
        );
    }
}
