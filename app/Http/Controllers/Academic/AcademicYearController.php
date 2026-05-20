<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Academic\UpdateAcademicYearRequest;
use App\Http\Resources\Academic\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    public function index(Request $request)
    {
        $years = QueryBuilder::for(AcademicYear::query())
            ->allowedFilters([
                AllowedFilter::exact('is_current'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(AcademicYearResource::collection($years));
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $validated = $request->validated();

        if ($request->boolean('is_current')) {
            $candidate = new AcademicYear($validated);
            $rejection = $this->rejectCurrentAcademicYearCandidate($candidate);
            if ($rejection !== null) {
                return $rejection;
            }
        }

        $year = DB::transaction(fn () => AcademicYear::create($validated));

        return $this->success(new AcademicYearResource($year), 'Academic year created', Response::HTTP_CREATED);
    }

    public function show(AcademicYear $academicYear)
    {
        return $this->success(new AcademicYearResource($academicYear));
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $validated = $request->validated();
        $candidate = clone $academicYear;
        $candidate->fill($validated);

        if ($candidate->is_current) {
            $rejection = $this->rejectCurrentAcademicYearCandidate($candidate);
            if ($rejection !== null) {
                return $rejection;
            }
        }

        DB::transaction(fn () => $academicYear->update($validated));

        return $this->success(new AcademicYearResource($academicYear->refresh()), 'Academic year updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (! $academicYear) {
            return $this->error('Année académique non trouvée.', Response::HTTP_NOT_FOUND);
        }

        // Check if there are enrollments for this year
        if ($academicYear->enrollments()->exists()) {
            return $this->error('Impossible de supprimer cette année académique car des inscriptions y sont associées.', Response::HTTP_CONFLICT);
        }

        DB::transaction(fn () => $academicYear->delete());

        return $this->success(null, 'Année académique supprimée avec succès.');
    }

    /**
     * Get the current academic year
     */
    public function current(): JsonResponse
    {
        $currentYear = AcademicYear::getCurrentYear();

        if (! $currentYear) {
            return $this->error('Aucune année académique actuelle définie.', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new AcademicYearResource($currentYear));
    }

    /**
     * Set an academic year as current
     */
    public function setCurrent(string $id): JsonResponse
    {
        $academicYear = AcademicYear::find($id);

        if (! $academicYear) {
            return $this->error('Année académique non trouvée.', Response::HTTP_NOT_FOUND);
        }

        $rejection = $this->rejectCurrentAcademicYearCandidate($academicYear);
        if ($rejection !== null) {
            return $rejection;
        }

        DB::transaction(function () use ($academicYear, $id) {
            AcademicYear::where('id', '!=', $id)->update(['is_current' => false]);
            $academicYear->update(['is_current' => true]);
        });

        return $this->success(new AcademicYearResource($academicYear->fresh()), 'Année académique définie comme actuelle.');
    }

    private function rejectCurrentAcademicYearCandidate(AcademicYear $academicYear): ?JsonResponse
    {
        $reason = $academicYear->currentIneligibilityReason();

        if ($reason === null) {
            return null;
        }

        return $this->error($reason, Response::HTTP_UNPROCESSABLE_ENTITY, [
            'is_current' => [$reason],
        ]);
    }
}
