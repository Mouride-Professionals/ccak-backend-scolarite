<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreCourseLogRequest;
use App\Http\Requests\Academic\UpdateCourseLogRequest;
use App\Http\Resources\Academic\CourseLogResource;
use App\Models\Course;
use App\Models\CourseLog;
use App\Models\FacultyMember;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CourseLogController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:course_logs.create')->only('store');
        $this->middleware('permission:course_logs.update')->only('update');
        $this->middleware('permission:course_logs.view')->only('courseLogs');
    }

    public function store(StoreCourseLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $schedule = Schedule::with('facultyMember')->findOrFail($data['schedule_id']);
        $sessionDate = Carbon::parse($data['session_date']);

        if (!$this->isSessionDateValid($schedule, $sessionDate)) {
            return $this->error('Session date does not match schedule constraints.', 422);
        }

        $facultyMember = FacultyMember::where('user_id', $request->user()->id)->first();
        if ($facultyMember && $schedule->faculty_member_id !== $facultyMember->id && !$request->user()->hasRole('ADMIN')) {
            return $this->error('You are not assigned to this schedule.', 403);
        }

        $log = DB::transaction(function () use ($data, $schedule, $request) {
            return CourseLog::create([
                'schedule_id' => $schedule->id,
                'faculty_member_id' => $schedule->faculty_member_id,
                'created_by_user_id' => $request->user()->id,
                'session_date' => $data['session_date'],
                'topics' => $data['topics'] ?? [],
                'chapters' => $data['chapters'] ?? [],
                'objectives' => $data['objectives'] ?? [],
                'notes' => $data['notes'] ?? null,
                'signed_at' => now(),
            ]);
        });

        return $this->success(new CourseLogResource($log), 'Course log created', 201);
    }

    public function update(UpdateCourseLogRequest $request, CourseLog $course_log): JsonResponse
    {
        if ($course_log->created_by_user_id !== $request->user()->id && !$request->user()->hasRole('ADMIN')) {
            return $this->error('Only the creator can update this log.', 403);
        }

        $data = $request->validated();
        if (!empty($data['session_date'])) {
            $course_log->loadMissing('schedule');
            $sessionDate = Carbon::parse($data['session_date']);
            if (!$this->isSessionDateValid($course_log->schedule, $sessionDate)) {
                return $this->error('Session date does not match schedule constraints.', 422);
            }
        }

        DB::transaction(fn() => $course_log->update($data));

        return $this->success(new CourseLogResource($course_log->refresh()), 'Course log updated');
    }

    public function courseLogs(Course $course): JsonResponse
    {
        $logs = CourseLog::query()
            ->whereHas('schedule', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->orderBy('session_date')
            ->get();

        return $this->success(CourseLogResource::collection($logs));
    }

    private function isSessionDateValid(Schedule $schedule, Carbon $sessionDate): bool
    {
        if ($schedule->starts_on && $sessionDate->lt(Carbon::parse($schedule->starts_on))) {
            return false;
        }

        if ($schedule->ends_on && $sessionDate->gt(Carbon::parse($schedule->ends_on))) {
            return false;
        }

        $dayOfWeek = $sessionDate->dayOfWeekIso;
        return $dayOfWeek === (int) $schedule->day_of_week;
    }
}
