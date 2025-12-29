<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class DeliberationSession extends Model
{
    use HasFactory;


    protected $table = 'deliberation_sessions';

    // Clé primaire de type UUID
    protected $keyType = 'string';
    public $incrementing = false;

    // Champs remplissables
    protected $fillable = [
        'id',
        'academic_program_id',
        'academic_year_id',
        'semester',
        'session_name',
        'session_date',
        'status',
        'presided_by',
        'jury_members',
    ];

    // Casts pour types spécifiques
    protected $casts = [
        'id' => 'string',
        'academic_program_id' => 'string',
        'academic_year_id' => 'string',
        'semester' => 'integer',
        'session_date' => 'date',
        'status' => 'string',
        'presided_by' => 'string',
        'jury_members' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Génération automatique de UUID à la création
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // Optionnel : Enum pour le status
    const STATUS_SCHEDULED = 'SCHEDULED';
    const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CLOSED = 'CLOSED';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CLOSED,
        ];
    }

    public function academicProgram()
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function president()
    {
        return $this->belongsTo(FacultyMember::class, 'presided_by');
    }

    public function juryMembers()
    {
        return $this->belongsToMany(
            FacultyMember::class,
            'deliberation_session_jury',
            'deliberation_session_id',
            'faculty_member_id'
        );
    }

    public function results()
    {
        return $this->hasMany(DeliberationResult::class, 'deliberation_session_id');
    }
}
