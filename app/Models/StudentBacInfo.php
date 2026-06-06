<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBacInfo extends Model
{
    use HasFactory;
    use UsesUuidV7;

    protected $table = 'student_bac_infos';

    protected $fillable = [
        'student_id',
        'serie',
        'year_of_bac',
        'bac_result_id',
        'first_round_average',
        'second_round_average',
        'bac_mention',
        'bac_institution',
    ];

    protected $casts = [
        'first_round_average' => 'decimal:2',
        'second_round_average' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
