<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
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
}
