<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseApiController;
use App\Jobs\SyncJob;
use App\Models\SyncLog;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends BaseApiController
{
    public function __construct(private readonly SyncService $syncService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $query = SyncLog::latest('started_at');

        if ($entityType = $request->query('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        return $this->success($query->paginate($perPage));
    }

    public function stats(): JsonResponse
    {
        $apiEntities = ['students', 'enrollments'];
        $staticEntities = ['degree_cycles', 'niveaux', 'ufr', 'departements', 'programmes', 'academic_years'];

        $result = [];

        foreach ($apiEntities as $entity) {
            $log = SyncLog::where('entity_type', $entity)
                ->latest('completed_at')
                ->first(['status', 'total_received', 'total_updated', 'completed_at']);

            $result[$entity] = [
                'is_static' => false,
                'last_synced_at' => $log?->completed_at,
                'total' => $log ? max($log->total_received, $log->total_updated) : null,
                'status' => $log?->status?->value,
            ];
        }

        foreach ($staticEntities as $entity) {
            $result[$entity] = ['is_static' => true];
        }

        return $this->success($result);
    }

    public function trigger(Request $request): JsonResponse
    {
        $entityType = $request->input('entity_type');

        if ($entityType && ! in_array($entityType, ['students', 'enrollments'], true)) {
            return $this->error("Entité inconnue : {$entityType}", 422);
        }

        SyncJob::dispatch($entityType);

        $label = $entityType ?? 'toutes les entités';

        return $this->success(null, "Synchronisation de « {$label} » lancée en arrière-plan. Consultez l'historique pour suivre l'avancement.", 202);
    }
}
