<?php

namespace App\Models;

use App\Enums\DegreeCycleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DegreeCycle extends Model
{
    protected $table = 'degree_cycles';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
        'type',
        'synced_from',
        'last_synced_at',
    ];

    protected $casts = [
        'type' => DegreeCycleType::class,
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class, 'degree_cycle_id');
    }
}
