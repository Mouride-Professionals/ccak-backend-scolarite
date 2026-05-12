<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddressType;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'type',
        'street',
        'city',
        'region',
        'department',
        'country',
        'is_primary',
    ];

    protected $casts = [
        'type' => AddressType::class,
        'is_primary' => 'boolean',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
