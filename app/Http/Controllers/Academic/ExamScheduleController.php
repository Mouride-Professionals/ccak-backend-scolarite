<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreExamScheduleRequest;
use App\Http\Requests\Academic\UpdateExamScheduleRequest;
use App\Http\Resources\Academic\ExamScheduleResource;
use App\Models\ExamSchedule;
use App\Models\ExamSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamScheduleController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:exam-sessions.view')->only(['index', 'show']);
        $this->middleware('permission:exam-sessions.create')->only('store');
        $this->middleware('permission:exam-sessions.update')->only('update');
        $this->middleware('permission:exam-sessions.delete')->only('destroy');
    }

    public function index(ExamSession $examSession): JsonResponse
    {
        $schedules = $examSession->schedules()
            ->with(['course', 'room', 'invigilators'])
            ->get();

        return $this->success(ExamScheduleResource::collection($schedules));
    }

    public function store(StoreExamScheduleRequest $request, ExamSession $examSession): JsonResponse
    {
        $data = $request->validated();
        $invigilatorIds = $data['invigilator_ids'];
        unset($data['invigilator_ids']);

        $schedule = DB::transaction(function () use ($examSession, $data, $invigilatorIds) {
            $schedule = $examSession->schedules()->create($data);
            $schedule->invigilators()->sync($invigilatorIds);

            return $schedule;
        });

        return $this->success(
            new ExamScheduleResource($schedule->load(['course', 'room', 'invigilators'])),
            'Examen planifié',
            201
        );
    }

    public function show(ExamSession $examSession, ExamSchedule $examSchedule): JsonResponse
    {
        $examSchedule->load(['course', 'room', 'invigilators']);

        return $this->success(new ExamScheduleResource($examSchedule));
    }

    public function update(UpdateExamScheduleRequest $request, ExamSession $examSession, ExamSchedule $examSchedule): JsonResponse
    {
        $data = $request->validated();
        $invigilatorIds = $data['invigilator_ids'] ?? null;
        unset($data['invigilator_ids']);

        DB::transaction(function () use ($examSchedule, $data, $invigilatorIds) {
            $examSchedule->update($data);
            if ($invigilatorIds !== null) {
                $examSchedule->invigilators()->sync($invigilatorIds);
            }
        });

        return $this->success(
            new ExamScheduleResource($examSchedule->fresh(['course', 'room', 'invigilators'])),
            'Examen mis à jour'
        );
    }

    public function destroy(ExamSession $examSession, ExamSchedule $examSchedule): JsonResponse
    {
        $examSchedule->delete();

        return $this->success(null, 'Examen supprimé');
    }

    public function listAll(Request $request): JsonResponse
    {
        $query = ExamSchedule::with(['course', 'room', 'invigilators', 'examSession']);

        if ($request->filled('academic_year_id')) {
            $query->whereHas('examSession', fn ($q) => $q->where('academic_year_id', $request->academic_year_id));
        }

        if ($request->filled('exam_session_id')) {
            $query->where('exam_session_id', $request->exam_session_id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $perPage = min((int) $request->query('limit', 50), 100);

        return $this->success(ExamScheduleResource::collection($query->paginate($perPage)));
    }

    public function checkConflicts(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'invigilator_ids' => ['nullable', 'array'],
            'invigilator_ids.*' => ['uuid'],
            'exclude_schedule_id' => ['nullable', 'uuid'], // for edit mode
        ]);

        $conflicts = [];

        // Check room conflict
        $roomConflict = ExamSchedule::where('room_id', $request->room_id)
            ->where('date', $request->date)
            ->where(function ($q) use ($request) {
                $q->where(fn ($q2) => $q2->where('start_time', '<', $request->end_time)->where('end_time', '>', $request->start_time));
            })
            ->when($request->exclude_schedule_id, fn ($q) => $q->where('id', '!=', $request->exclude_schedule_id))
            ->with('course')
            ->first();

        if ($roomConflict) {
            $conflicts[] = [
                'type' => 'room',
                'message' => "La salle est déjà réservée de {$roomConflict->start_time} à {$roomConflict->end_time} pour \"{$roomConflict->course?->name}\".",
            ];
        }

        // Check invigilator conflicts
        if (! empty($request->invigilator_ids)) {
            $invigilatorConflicts = ExamSchedule::whereHas('invigilators', fn ($q) => $q->whereIn('faculty_members.id', $request->invigilator_ids)
            )
                ->where('date', $request->date)
                ->where(fn ($q) => $q->where('start_time', '<', $request->end_time)->where('end_time', '>', $request->start_time))
                ->when($request->exclude_schedule_id, fn ($q) => $q->where('id', '!=', $request->exclude_schedule_id))
                ->with(['invigilators', 'course'])
                ->get();

            foreach ($invigilatorConflicts as $conflict) {
                $names = $conflict->invigilators
                    ->whereIn('id', $request->invigilator_ids)
                    ->pluck('full_name')
                    ->join(', ');
                $conflicts[] = [
                    'type' => 'invigilator',
                    'message' => "{$names} déjà affecté(e)(s) de {$conflict->start_time} à {$conflict->end_time} pour \"{$conflict->course?->name}\".",
                ];
            }
        }

        return $this->success([
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ]);
    }
}
