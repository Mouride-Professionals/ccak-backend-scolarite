<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliberationResult extends Model
{
    use HasFactory;
    use UsesUuidV7;

    public const DECISIONS = ['ADMITTED', 'ADMITTED_COMPENSATION', 'RESIT', 'FAILED', 'EXCLUDED'];

    public const DECISION_ADMITTED = 'ADMITTED';

    public const DECISION_ADMITTED_COMPENSATION = 'ADMITTED_COMPENSATION';

    public const DECISION_RESIT = 'RESIT';

    public const DECISION_FAILED = 'FAILED';

    public const DECISION_EXCLUDED = 'EXCLUDED';

    public const HONOR_LEVELS = ['PASSABLE', 'ASSEZ_BIEN', 'BIEN', 'TRES_BIEN'];

    public const HONOR_LEVEL_PASSABLE = 'PASSABLE';

    public const HONOR_LEVEL_ASSEZ_BIEN = 'ASSEZ_BIEN';

    public const HONOR_LEVEL_BIEN = 'BIEN';

    public const HONOR_LEVEL_TRES_BIEN = 'TRES_BIEN';

    protected $fillable = [
        'id',
        'deliberation_session_id',
        'student_id',
        'decision',
        'jury_remarks',
        'is_with_honors',
        'honor_level',
    ];

    protected $casts = [
        'is_with_honors' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function deliberationSession(): BelongsTo
    {
        return $this->belongsTo(DeliberationSession::class, 'deliberation_session_id');
    }
}
