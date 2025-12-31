<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\DeliberationResult;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StudentDeliberationController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:deliberation_results.view')->only('history');
    }

    /**
     * Get deliberation history for a student
     * GET /api/students/{id}/deliberations
     */
    public function history(Request $request, string $student_id)
    {
        $deliberations = QueryBuilder::for(DeliberationResult::query())
            ->where('student_id', $student_id)
            ->with(['deliberationSession.academicProgram', 'deliberationSession.academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('decision'),
                AllowedFilter::exact('is_with_honors'),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success([
            'student_id' => $student_id,
            'deliberations' => $deliberations->items(),
            'total' => $deliberations->total(),
            'pagination' => [
                'current_page' => $deliberations->currentPage(),
                'last_page' => $deliberations->lastPage(),
                'per_page' => $deliberations->perPage(),
            ],
        ]);
    }
}
