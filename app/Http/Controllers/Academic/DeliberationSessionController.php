<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\DeliberationSession;
use App\Services\DeliberationService;
use App\Services\MinutesGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeliberationSessionController extends Controller
{
    protected $service;
    protected $minutesService;

    public function __construct(DeliberationService $service, MinutesGeneratorService $minutesService)
    {
        $this->service = $service;
        $this->minutesService = $minutesService;
        //
        $this->middleware('permission:deliberation_sessions.view')->only(['index', 'show']);
        $this->middleware('permission:deliberation_sessions.create')->only('store');
        $this->middleware('permission:deliberation_sessions.update')->only('update');
        $this->middleware('permission:deliberation_sessions.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        $query = DeliberationSession::query()
            ->with(['academicProgram', 'academicYear', 'president', 'juryMembers']);

        if ($request->has('academic_program_id')) {
            $query->where('academic_program_id', $request->query('academic_program_id'));
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        if ($request->has('presided_by')) {
            $query->where('presided_by', $request->query('presided_by'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('session_name')) {
            $query->where('session_name', $request->query('session_name'));
        }

        $sessions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($sessions);
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

        $session = $this->service->create($data);

        return response()->json($session, 201);
    }

    public function show($id)
    {
        $session = $this->service->getById($id);
        return $session ? response()->json($session) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'semester', 'session_name', 'session_date', 'status', 'presided_by', 'jury_members'
        ]);

        $session = $this->service->update($id, $data);

        return $session ? response()->json($session) : response()->json(['message' => 'Not found'], 404);
    }

    public function destroy($id)
    {
        return $this->service->delete($id)
            ? response()->json(['message' => 'Deleted'])
            : response()->json(['message' => 'Not found'], 404);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:SCHEDULED,IN_PROGRESS,COMPLETED,CLOSED'
        ]);

        $session = $this->service->changeStatus($id, $request->status);

        return $session ? response()->json($session) : response()->json(['message' => 'Invalid status or session not found'], 400);
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
