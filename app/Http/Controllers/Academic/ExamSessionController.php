<?php

namespace App\Http\Controllers\Academic;

use App\Enums\ExamSessionStatus;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreExamSessionRequest;
use App\Http\Requests\Academic\UpdateExamSessionRequest;
use App\Http\Resources\Academic\ExamSessionResource;
use App\Models\ExamSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExamSessionController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:exam-sessions.view')->only(['index', 'show']);
        $this->middleware('permission:exam-sessions.create')->only('store');
        $this->middleware('permission:exam-sessions.update')->only(['update', 'publish', 'close']);
        $this->middleware('permission:exam-sessions.delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $sessions = QueryBuilder::for(ExamSession::query())
            ->with(['academicYear'])
            ->withCount('schedules')
            ->allowedFilters([
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('semester_number'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['created_at', 'start_date', 'name'])
            ->defaultSort('-start_date')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());

        return $this->success(ExamSessionResource::collection($sessions), 'Sessions d\'examen récupérées');
    }

    public function store(StoreExamSessionRequest $request): JsonResponse
    {
        $session = ExamSession::create(array_merge(
            $request->validated(),
            ['status' => ExamSessionStatus::DRAFT]
        ));

        return $this->success(
            new ExamSessionResource($session->load('academicYear')),
            'Session d\'examen créée',
            201
        );
    }

    public function show(ExamSession $examSession): JsonResponse
    {
        $examSession->load(['academicYear', 'schedules.course', 'schedules.room', 'schedules.invigilators']);

        return $this->success(new ExamSessionResource($examSession));
    }

    public function update(UpdateExamSessionRequest $request, ExamSession $examSession): JsonResponse
    {
        $examSession->update($request->validated());

        return $this->success(
            new ExamSessionResource($examSession->load('academicYear')),
            'Session mise à jour'
        );
    }

    public function destroy(ExamSession $examSession): JsonResponse
    {
        $examSession->delete();

        return $this->success(null, 'Session supprimée');
    }

    public function publish(ExamSession $examSession): JsonResponse
    {
        $this->authorize('publish', $examSession);

        if ($examSession->status !== ExamSessionStatus::DRAFT) {
            return $this->error('Seule une session en brouillon peut être publiée.', 422);
        }

        if ($examSession->schedules()->count() === 0) {
            return $this->error('Impossible de publier une session sans examens planifiés.', 422);
        }

        $examSession->update(['status' => ExamSessionStatus::PUBLISHED]);

        return $this->success(
            new ExamSessionResource($examSession->fresh('academicYear')),
            'Session publiée'
        );
    }

    public function close(ExamSession $examSession): JsonResponse
    {
        $this->authorize('close', $examSession);

        if ($examSession->status !== ExamSessionStatus::PUBLISHED) {
            return $this->error('Seule une session publiée peut être clôturée.', 422);
        }

        $examSession->update(['status' => ExamSessionStatus::CLOSED]);

        return $this->success(
            new ExamSessionResource($examSession->fresh('academicYear')),
            'Session clôturée'
        );
    }
}
