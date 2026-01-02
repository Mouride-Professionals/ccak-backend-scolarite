<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\CheckScheduleAvailabilityRequest;
use App\Http\Requests\Academic\StoreScheduleRequest;
use App\Http\Resources\Academic\ScheduleResource;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\CourseEnrollment;
use App\Models\FacultyMember;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:schedules.create')->only('store');
        $this->middleware('permission:schedules.view')->only(['programSchedule', 'facultySchedule', 'studentSchedule', 'checkAvailability']);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $conflicts = $this->findConflicts($data);
        if (!empty($conflicts)) {
            return $this->error('Schedule conflicts detected.', 422, ['conflicts' => $conflicts]);
        }

        $schedule = DB::transaction(fn() => Schedule::create($data));

        return $this->success(
            new ScheduleResource($schedule->load(['course', 'facultyMember', 'room', 'activityType'])),
            'Schedule created',
            201
        );
    }

    public function programSchedule(Request $request, AcademicProgram $program): JsonResponse
    {
        $academicYearId = $request->input('academic_year_id');
        $semester = $request->integer('semester');

        $schedules = Schedule::query()
            ->with(['course.courseUnit', 'facultyMember', 'room', 'activityType'])
            ->whereHas('course.courseUnit', function ($query) use ($program) {
                $query->where('academic_program_id', $program->id);
            })
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->when($semester > 0, function ($query) use ($semester) {
                $query->where('semester_number', $semester);
            })
            ->get()
            ->groupBy('day_of_week');

        return $this->success([
            'program_id' => $program->id,
            'days' => $schedules->map(fn($items) => ScheduleResource::collection($items)->resolve()),
        ]);
    }

    public function facultySchedule(Request $request, FacultyMember $faculty_member): JsonResponse
    {
        $academicYearId = $request->input('academic_year_id');

        $schedules = Schedule::query()
            ->with(['course', 'room', 'activityType'])
            ->where('faculty_member_id', $faculty_member->id)
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->get();

        $totalHours = $schedules->sum(function ($schedule) {
            $start = strtotime($schedule->start_time);
            $end = strtotime($schedule->end_time);
            return max(0, ($end - $start) / 3600);
        });

        return $this->success([
            'faculty_member_id' => $faculty_member->id,
            'total_hours' => round($totalHours, 2),
            'schedules' => ScheduleResource::collection($schedules),
        ]);
    }

    public function studentSchedule(Request $request, Student $student): JsonResponse
    {
        $academicYearId = $request->input('academic_year_id') ?? AcademicYear::current()->value('id');

        $courseIds = CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', CourseEnrollment::STATUS_ENROLLED)
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->pluck('course_id');

        $schedules = Schedule::query()
            ->with(['course', 'facultyMember', 'room', 'activityType'])
            ->whereIn('course_id', $courseIds)
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->get()
            ->groupBy('day_of_week');

        return $this->success([
            'student_id' => $student->id,
            'days' => $schedules->map(fn($items) => ScheduleResource::collection($items)->resolve()),
        ]);
    }

    public function checkAvailability(CheckScheduleAvailabilityRequest $request): JsonResponse
    {
        $data = $request->validated();

        $conflicts = $this->findConflicts($data);

        return $this->success([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
        ]);
    }

    /** @return array<string, mixed> */
    private function findConflicts(array $data): array
    {
        $start = $data['start_time'];
        $end = $data['end_time'];

        $baseQuery = Schedule::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('semester_number', $data['semester_number'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });

        $conflicts = [];

        if (!empty($data['room_id'])) {
            $roomConflicts = (clone $baseQuery)
                ->where('room_id', $data['room_id'])
                ->with(['room'])
                ->get();

            if ($roomConflicts->isNotEmpty()) {
                $conflicts['room'] = ScheduleResource::collection($roomConflicts)->resolve();
            }
        }

        if (!empty($data['faculty_member_id'])) {
            $facultyConflicts = (clone $baseQuery)
                ->where('faculty_member_id', $data['faculty_member_id'])
                ->with(['facultyMember'])
                ->get();

            if ($facultyConflicts->isNotEmpty()) {
                $conflicts['faculty_member'] = ScheduleResource::collection($facultyConflicts)->resolve();
            }
        }

        return $conflicts;
    }
}
