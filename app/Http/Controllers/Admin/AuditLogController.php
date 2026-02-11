<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditLogController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware(['role:ADMIN', 'permission:audits.view']);
    }

    public function index(Request $request): JsonResponse
    {
        $audits = QueryBuilder::for(Audit::query())
            ->allowedFilters([
                AllowedFilter::exact('event'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('auditable_type'),
                AllowedFilter::exact('auditable_id'),
            ])
            ->allowedSorts(['created_at', 'event'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 50)
            ->appends($request->query());

        return $this->success($audits, 'Audit logs retrieved successfully');
    }

    public function show(int $audit): JsonResponse
    {
        return $this->success(Audit::query()->findOrFail($audit), 'Audit log retrieved successfully');
    }

    public function forModel(Request $request, string $model, string $id): JsonResponse
    {
        $auditableType = str_contains($model, '\\')
            ? $model
            : 'App\\Models\\' . ltrim($model, '\\');

        $audits = QueryBuilder::for(Audit::query()->where('auditable_type', $auditableType)->where('auditable_id', $id))
            ->allowedFilters([
                AllowedFilter::exact('event'),
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts(['created_at', 'event'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 50)
            ->appends($request->query());

        return $this->success($audits, 'Audit logs for model retrieved successfully');
    }
}
