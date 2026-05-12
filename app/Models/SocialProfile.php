<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialProfile extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $fillable = [
        'profilable_id',
        'profilable_type',
        'family_status',
        'number_of_children',
        'is_employed',
        'socio_professional_category',
        'student_regime',
    ];

    protected $casts = [
        'number_of_children' => 'integer',
        'is_employed' => 'boolean',
    ];

    public function profilable(): MorphTo
    {
        return $this->morphTo();
    }
}
