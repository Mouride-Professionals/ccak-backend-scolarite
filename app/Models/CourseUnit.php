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
        'coefficient',
        'type',
        'is_active',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'is_active'   => 'boolean',
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

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $sub) use ($term, $likeOperator): void {
            $sub->where('name', $likeOperator, "%{$term}%")
                ->orWhere('code', $likeOperator, "%{$term}%");
        });
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
