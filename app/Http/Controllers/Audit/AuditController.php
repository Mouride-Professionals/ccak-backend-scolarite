<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Liste de tous les audits
     */
    public function index(Request $request)
    {
        // Filtrage optionnel par modèle ou utilisateur
        $query = Audit::query();

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->latest()->paginate(50));
    }

    /**
     * Voir les audits d'un modèle spécifique
     */
    public function show($model, $id)
    {
        $audits = Audit::where('auditable_type', $model)
                        ->where('auditable_id', $id)
                        ->latest()
                        ->get();

        return response()->json($audits);
    }
}
