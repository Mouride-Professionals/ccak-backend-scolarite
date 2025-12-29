<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Academic\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AcademicYearController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:academic_years.view')->only(['index', 'show']);
        $this->middleware('permission:academic_years.create')->only('store');
        $this->middleware('permission:academic_years.update')->only('update');
        $this->middleware('permission:academic_years.delete')->only('destroy');
    }

    public function index()
    {
        $years = QueryBuilder::for(AcademicYear::query())
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
            ->get();

        return $this->success($years);
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $year = AcademicYear::create($request->validated());

        return $this->success($year, 'Academic year created', Response::HTTP_CREATED);
    }

    public function show(AcademicYear $academicYear)
    {
        return $this->success($academicYear);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->validated());

        return $this->success($academicYear->refresh(), 'Academic year updated');
    }

   public function destroy(string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée.',
            ], 404);
        }

        // Check if there are enrollments for this year
        if ($academicYear->enrollments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une année académique avec des inscriptions existantes.',
            ], 422);
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Année académique supprimée avec succès.',
        ]);
    }

    /**
     * Get the current academic year
    */
    public function current(): JsonResponse
    {
        $currentYear = AcademicYear::getCurrentYear();

        if (!$currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année académique actuelle n\'est définie.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $currentYear,
        ]);
    }

    /**
     * Set an academic year as current
     */
    public function setCurrent(string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée.',
            ], 404);
        }

        // Remove current status from all other years
        AcademicYear::where('id', '!=', $id)->update(['is_current' => false]);

        // Set this year as current
        $academicYear->update(['is_current' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Année académique définie comme actuelle.',
            'data' => $academicYear->fresh(),
        ]);
    }
}
