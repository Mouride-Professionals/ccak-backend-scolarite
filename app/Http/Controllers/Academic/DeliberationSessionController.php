<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\DeliberationSession;
use App\Services\DeliberationService;
use App\Services\MinutesGeneratorService;
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
        $this->middleware('permission:deliberations.view')->only(['index', 'show']);
        $this->middleware('permission:deliberations.create')->only('store');
        $this->middleware('permission:deliberations.update')->only('update');
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

        return $this->success($sessions, 'Deliberation sessions retrieved successfully');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_program_id' => 'required|uuid',
            'academic_year_id' => 'required|uuid',
            'semester' => 'required|integer',
            'session_name' => 'required|string|max:255',
            'session_date' => 'required|date',
            'status' => 'sometimes|in:SCHEDULED,IN_PROGRESS,COMPLETED,CLOSED',
            'presided_by' => 'required|uuid',
            'jury_members' => 'nullable|array',
        ]);

        $session = DB::transaction(fn() => $this->service->create($data));

        return $this->success($session, 'Deliberation session created', 201);
    }

    public function show($id)
    {
        $session = $this->service->getById($id);
        return $session ? $this->success($session) : $this->error('Not found', 404);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'semester',
            'session_name',
            'session_date',
            'status',
            'presided_by',
            'jury_members'
        ]);

        $session = DB::transaction(fn() => $this->service->update($id, $data));

        return $session ? $this->success($session) : $this->error('Not found', 404);
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

        return $session ? $this->success($session) : $this->error('Invalid status or session not found', 400);
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
