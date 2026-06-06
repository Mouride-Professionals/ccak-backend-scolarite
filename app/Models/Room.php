<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'room_number',
        'name',
        'building',
        'capacity',
        'type',
        'equipment',
        'is_available',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_available' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeIsAvailable(Builder $query, mixed $value = true): Builder
    {
        $isAvailable = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isAvailable === null) {
            return $query;
        }

        return $query->where('is_available', $isAvailable);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $sub) use ($term, $likeOperator): void {
            $sub->where('room_number', $likeOperator, "%{$term}%")
                ->orWhere('name', $likeOperator, "%{$term}%")
                ->orWhere('building', $likeOperator, "%{$term}%");
        });
    }
}
