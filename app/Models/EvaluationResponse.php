<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationResponse extends Model
{
    use HasFactory, UsesUuidV7;

    protected $fillable = [
        'evaluation_id',
        'student_id',
        'responses',
        'rating_scores',
        'comments',
        'is_anonymous',
        'submitted_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'rating_scores' => 'array',
        'is_anonymous' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
