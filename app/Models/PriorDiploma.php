<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriorDiploma extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'diplomable_id',
        'diplomable_type',
        'name',
        'year',
        'mention',
        'institution',
    ];

    public function diplomable(): MorphTo
    {
        return $this->morphTo();
    }
}
