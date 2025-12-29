<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'name',
        'code',
        'dean_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Audit configuration
    public $auditEvents = ['created', 'updated', 'deleted'];
    public $auditExclude = ['created_at', 'updated_at'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Custom audit transformation
     */
    public function transformAudit(array $data): array
    {
        $data = parent::transformAudit($data);

        // Add dean information
        if ($this->dean) {
            $data['dean_email'] = $this->dean->email;
        }

        return $data;
    }
}
