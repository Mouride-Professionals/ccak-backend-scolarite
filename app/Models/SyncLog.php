<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Models\Concerns\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'batch_date',
        'source',
        'entity_type',
        'total_received',
        'total_created',
        'total_updated',
        'total_errors',
        'error_details',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'status' => SyncStatus::class,
        'error_details' => 'array',
        'batch_date' => 'date',
    ];
}
