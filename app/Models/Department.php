<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UsesUuidV7;

    protected $fillable = [
        'faculty_id',
        'name',
        'code',
        'head_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Audit configuration for SoftDeletes
    public $auditEvents = ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

    public $auditExclude = ['created_at', 'updated_at', 'deleted_at'];

    /** @return BelongsTo<Faculty, Department> */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(AcademicProgram::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    /**
     * Custom audit transformation
     */
    public function transformAudit(array $data): array
    {
        $data = parent::transformAudit($data);

        // Add faculty information
        if ($this->faculty) {
            $data['faculty_name'] = $this->faculty->name;
            $data['faculty_code'] = $this->faculty->code;
        }

        // Add head information
        if ($this->head) {
            $data['head_name'] = $this->head->email;
        }

        return $data;
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
}
