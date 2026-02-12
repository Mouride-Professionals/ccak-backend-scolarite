<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreDeliberationSessionRequest;
use App\Http\Requests\Academic\UpdateDeliberationSessionRequest;
use App\Http\Resources\Academic\DeliberationResultResource;
use App\Http\Resources\Academic\DeliberationSessionResource;
use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use App\Models\Student;
use App\Services\DeliberationService;
use App\Services\MinutesGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DeliberationSessionController extends BaseApiController
{
    protected $service;
    protected $minutesService;

    public function __construct(DeliberationService $service, MinutesGeneratorService $minutesService)
    {
        $this->service = $service;
        $this->minutesService = $minutesService;
        $this->middleware('permission:deliberations.view')->only(['index', 'show', 'getStudents']);
        $this->middleware('permission:deliberations.create')->only('store');
        $this->middleware('permission:deliberations.update')->only(['update', 'changeStatus', 'start', 'saveDecision', 'complete']);
        $this->middleware('permission:deliberations.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $sessions = QueryBuilder::for(DeliberationSession::query())
            ->with(['academicProgram', 'academicYear', 'president', 'juryMembers'])
            ->allowedIncludes(['academicProgram', 'academicYear', 'president', 'juryMembers'])
            ->allowedFilters([
                AllowedFilter::exact('academic_program_id'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('presided_by'),
                AllowedFilter::exact('status'),
                AllowedFilter::partial('session_name'),
            ])
            ->allowedSorts(['created_at', 'session_date'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(
            DeliberationSessionResource::collection($sessions),
            'Deliberation sessions retrieved successfully'
        );
    }

    public function store(StoreDeliberationSessionRequest $request)
    {
        $session = DB::transaction(fn() => $this->service->create($request->validated()));

        return $this->success(
            new DeliberationSessionResource($session),
            'Deliberation session created',
            201
        );
    }

    public function show($id)
    {
        $session = $this->service->getById($id);
        return $session
            ? $this->success(new DeliberationSessionResource($session))
            : $this->error('Not found', 404);
    }

    public function update(UpdateDeliberationSessionRequest $request, $id)
    {
        $session = DB::transaction(fn() => $this->service->update($id, $request->validated()));

        return $session
            ? $this->success(new DeliberationSessionResource($session))
            : $this->error('Not found', 404);
    }

    public function destroy($id)
    {
        return $this->service->delete($id)
            ? $this->success(null, 'Deleted')
            : $this->error('Not found', 404);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SCHEDULED,IN_PROGRESS,COMPLETED,CLOSED'
        ]);

        $session = DB::transaction(fn() => $this->service->changeStatus($id, $request->status));

        return $session
            ? $this->success(new DeliberationSessionResource($session))
            : $this->error('Invalid status or session not found', 400);
    }

    public function start(DeliberationSession $deliberationSession): JsonResponse
    {
        $this->authorize('start', $deliberationSession);

        if ($deliberationSession->status !== DeliberationSession::STATUS_SCHEDULED) {
            return $this->error('La session doit être au statut SCHEDULED pour être démarrée.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $started = $this->service->startDeliberation($deliberationSession);

            return $this->success([
                'session' => new DeliberationSessionResource($started['session']->load(['academicProgram', 'academicYear', 'president', 'juryMembers'])),
                'students' => $started['students'],
                'recommendations' => $started['recommendations'],
            ], 'Deliberation session started successfully');
        } catch (\Throwable $e) {
            return $this->error('Impossible de démarrer la session de délibération: ' . $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function saveDecision(Request $request, DeliberationSession $deliberationSession, Student $student): JsonResponse
    {
        $this->authorize('saveDecision', $deliberationSession);

        if ($deliberationSession->status !== DeliberationSession::STATUS_IN_PROGRESS) {
            return $this->error('La session doit être en cours (IN_PROGRESS).', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:' . implode(',', DeliberationResult::DECISIONS)],
            'is_with_honors' => ['sometimes', 'boolean'],
            'honor_level' => ['nullable', 'in:' . implode(',', DeliberationResult::HONOR_LEVELS)],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = DB::transaction(function () use ($deliberationSession, $student, $validated) {
            return DeliberationResult::updateOrCreate(
                [
                    'deliberation_session_id' => $deliberationSession->id,
                    'student_id' => $student->id,
                ],
                [
                    'decision' => $validated['decision'],
                    'is_with_honors' => (bool) ($validated['is_with_honors'] ?? false),
                    'honor_level' => $validated['honor_level'] ?? null,
                    'jury_remarks' => $validated['comments'] ?? null,
                ]
            );
        });

        return $this->success(
            new DeliberationResultResource($decision->load(['student', 'deliberationSession'])),
            'Décision enregistrée avec succès.'
        );
    }

    public function complete(DeliberationSession $deliberationSession): JsonResponse
    {
        $this->authorize('complete', $deliberationSession);

        if ($deliberationSession->status !== DeliberationSession::STATUS_IN_PROGRESS) {
            return $this->error('La session doit être au statut IN_PROGRESS pour être clôturée.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->service->completeDeliberation($deliberationSession);

            return $this->success(
                new DeliberationSessionResource($deliberationSession->fresh(['academicProgram', 'academicYear', 'president', 'juryMembers'])),
                'Deliberation session completed successfully'
            );
        } catch (\Throwable $e) {
            return $this->error('Impossible de finaliser la session: ' . $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function getStudents(DeliberationSession $deliberationSession): JsonResponse
    {
        $this->authorize('view', $deliberationSession);

        $students = $deliberationSession->results()
            ->with('student')
            ->get()
            ->map(function (DeliberationResult $result): array {
                return [
                    'student_id' => $result->student_id,
                    'student' => $result->student ? [
                        'id' => $result->student->id,
                        'student_number' => $result->student->student_number,
                        'full_name' => $result->student->full_name,
                    ] : null,
                    'decision' => $result->decision,
                    'is_with_honors' => $result->is_with_honors,
                    'honor_level' => $result->honor_level,
                    'comments' => $result->jury_remarks,
                ];
            })
            ->values();

        return $this->success([
            'session' => new DeliberationSessionResource($deliberationSession),
            'students' => $students,
        ], 'Students for deliberation session retrieved successfully');
    }


    /**
     * Generate minutes for a deliberation session
     * GET /api/deliberations/{id}/minutes
     */
    public function generateMinutes($id)
    {
        $deliberation_session = $this->service->getById($id);
        if ($deliberation_session->status !== DeliberationSession::STATUS_COMPLETED) {
            return $this->error('Session must be COMPLETED to generate minutes', Response::HTTP_BAD_REQUEST);
        }

        try {
            $path = $this->minutesService->generate($deliberation_session);

            return response()->download($path, "minutes_{$deliberation_session->id}.pdf");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
