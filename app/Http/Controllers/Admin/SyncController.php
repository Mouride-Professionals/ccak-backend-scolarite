<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CcakApiException;
use App\Http\Controllers\BaseApiController;
use App\Models\SyncLog;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends BaseApiController
{
    public function __construct(private readonly SyncService $syncService)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $logs = SyncLog::latest('started_at')->paginate($perPage);

        return $this->success($logs);
    }

    public function trigger(): JsonResponse
    {
        try {
            $logs = $this->syncService->syncAll();

            return $this->success(array_values($logs), 'Synchronisation déclenchée avec succès.');
        } catch (CcakApiException $e) {
            return $this->error("Erreur API CCAK : {$e->getMessage()}", 502);
        } catch (\Throwable $e) {
            return $this->error("Erreur inattendue : {$e->getMessage()}", 500);
        }
    }
}
