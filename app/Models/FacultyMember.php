<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyMember extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $table = 'faculty_members';
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Deliberation roles
    public function presidedSessions(): HasMany
    {
        return $this->hasMany(DeliberationSession::class, 'presided_by');
    }

    public function jurySessions(): BelongsToMany
    {
        return $this->belongsToMany(
            DeliberationSession::class,
            'deliberation_session_jury',
            'faculty_member_id',
            'deliberation_session_id'
        );
    }
}
