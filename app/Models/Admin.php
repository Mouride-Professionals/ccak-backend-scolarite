<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class Admin extends Model
{
    use HasFactory, UsesUuidV7;

    protected $table = 'admins';
    protected $fillable = [
        'user_id',
        'full_name',
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'reviewed_by');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where('full_name', $likeOperator, "%{$term}%");
    }
}
