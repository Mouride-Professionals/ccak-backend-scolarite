<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseUnit extends Model
{
    use HasFactory;
    use UsesUuidV7;

    public const TYPES = ['OBLIGATOIRE', 'OPTIONNEL'];

    protected $fillable = [
        'academic_program_id',
        'code',
        'name',
        'semester_number',
        'credits',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Audit configuration
    public $auditEvents = ['created', 'updated', 'deleted'];
    public $auditExclude = ['created_at', 'updated_at'];

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Custom audit transformation
     */
    public function transformAudit(array $data): array
    {
        $data = parent::transformAudit($data);

        // Add academic program information
        if ($this->academicProgram) {
            $data['academic_program_name'] = $this->academicProgram->name;
            $data['program_level'] = $this->academicProgram->level;
        }

        return $data;
    }
}
