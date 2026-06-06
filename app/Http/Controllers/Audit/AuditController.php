<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:audits.view')->only(['index', 'show']);
    }

    /**
     * Liste de tous les audits
     */
    public function index(Request $request)
    {
        $audits = QueryBuilder::for(Audit::query())
            ->allowedFilters([
                AllowedFilter::exact('auditable_type'),
                AllowedFilter::exact('auditable_id'),
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 50)
            ->appends($request->query());

        return $this->success($audits, 'Audits retrieved successfully');
    }

    /**
     * Voir les audits d'un modèle spécifique
     */
    public function show($model, $id)
    {
        $audits = Audit::where('auditable_type', $model)
            ->where('auditable_id', $id)
            ->latest()
            ->paginate(request()->integer('per_page') ?? 50)
            ->appends(request()->query());

        return $this->success($audits, 'Audit records retrieved successfully');
    }
}
