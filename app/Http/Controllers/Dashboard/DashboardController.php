<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardFiltersRequest;
use App\Http\Requests\Dashboard\EnrollmentsTrendRequest;
use App\Http\Requests\Dashboard\RecentActivitiesRequest;
use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use App\Models\Enrollment;
use App\Services\Dashboard\DashboardFilterService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

#[Group('Dashboard', 'Dashboard analytics endpoints for KPI cards, charts, and activity feed.')]
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardFilterService $filters,
    ) {}

    #[Endpoint(operationId: 'dashboardOverview')]
    #[QueryParameter('academic_year_id', 'Academic year UUID filter', type: 'string', format: 'uuid')]
    #[QueryParameter('semester', 'Semester number (1..12)', type: 'integer', example: 1)]
    #[QueryParameter('date_from', 'Start date in YYYY-MM-DD', type: 'string', format: 'date', example: '2026-01-01')]
    #[QueryParameter('date_to', 'End date in YYYY-MM-DD', type: 'string', format: 'date', example: '2026-12-31')]
    #[QueryParameter('department_id', 'Department UUID filter', type: 'string', format: 'uuid')]
    #[QueryParameter('programme_id', 'Academic program UUID filter', type: 'string', format: 'uuid')]
    #[QueryParameter('faculty_id', 'Faculty UUID filter', type: 'string', format: 'uuid')]
    #[Response(status: 200, description: 'Overview KPIs', examples: [
        'success' => true,
        'data' => [
            'total_students' => 2400,
            'total_enrollments' => 342,
            'total_deliberations' => 12,
            'pending_items' => 23,
        ],
        'meta' => [
            'generated_at' => '2026-02-12T17:00:00Z',
            'filters' => [],
        ],
    ])]
    public function overview(DashboardFiltersRequest $request): JsonResponse
    {
        $filters = $request->filters();

        $enrollmentQuery = Enrollment::query();
        $this->filters->applyEnrollmentFilters($enrollmentQuery, $filters);

        $deliberationQuery = DeliberationSession::query();
        $this->filters->applyDeliberationFilters($deliberationQuery, $filters);

        $totalEnrollments = (clone $enrollmentQuery)->count();
        $totalStudents = (clone $enrollmentQuery)->distinct('student_id')->count('student_id');
        $totalDeliberations = (clone $deliberationQuery)->count();

        $pendingEnrollments = (clone $enrollmentQuery)
            ->where('status', RegistrationStatus::PENDING_VALIDATION)
            ->count();

        $pendingDeliberations = (clone $deliberationQuery)
            ->whereIn('status', [
                DeliberationSession::STATUS_SCHEDULED,
                DeliberationSession::STATUS_IN_PROGRESS,
            ])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'total_enrollments' => $totalEnrollments,
                'total_deliberations' => $totalDeliberations,
                'pending_items' => $pendingEnrollments + $pendingDeliberations,
            ],
            'meta' => [
                'generated_at' => CarbonImmutable::now()->utc()->format('Y-m-d\\TH:i:s\\Z'),
                'filters' => $filters,
            ],
        ]);
    }

    #[Endpoint(operationId: 'dashboardStudentsByLevel')]
    #[Response(status: 200, description: 'Students distribution by level', examples: [
        'success' => true,
        'data' => [
            ['niveau' => 'L1', 'licence' => 220, 'master' => 0],
            ['niveau' => 'L2', 'licence' => 490, 'master' => 0],
            ['niveau' => 'L3', 'licence' => 180, 'master' => 0],
            ['niveau' => 'M1', 'licence' => 0, 'master' => 430],
            ['niveau' => 'M2', 'licence' => 0, 'master' => 120],
        ],
    ])]
    public function studentsByLevel(DashboardFiltersRequest $request): JsonResponse
    {
        $filters = $request->filters();

        $query = Enrollment::query()
            ->join('academic_programs', 'academic_programs.id', '=', 'enrollments.academic_program_id');

        $this->filters->applyEnrollmentFilters($query, $filters);

        $rows = $query
            ->selectRaw('academic_programs.level as level, enrollments.current_semester as semester, COUNT(DISTINCT enrollments.student_id) as total')
            ->groupBy('academic_programs.level', 'enrollments.current_semester')
            ->get();

        $levels = [
            'L1' => ['niveau' => 'L1', 'licence' => 0, 'master' => 0],
            'L2' => ['niveau' => 'L2', 'licence' => 0, 'master' => 0],
            'L3' => ['niveau' => 'L3', 'licence' => 0, 'master' => 0],
            'M1' => ['niveau' => 'M1', 'licence' => 0, 'master' => 0],
            'M2' => ['niveau' => 'M2', 'licence' => 0, 'master' => 0],
        ];

        foreach ($rows as $row) {
            $bucket = $this->resolveLevelBucket((string) $row->level, (int) $row->semester);
            if ($bucket === null || ! isset($levels[$bucket])) {
                continue;
            }

            if (str_starts_with($bucket, 'L')) {
                $levels[$bucket]['licence'] += (int) $row->total;
            }

            if (str_starts_with($bucket, 'M')) {
                $levels[$bucket]['master'] += (int) $row->total;
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($levels),
        ]);
    }

    #[Endpoint(operationId: 'dashboardEnrollmentsTrend')]
    #[QueryParameter('period', 'Window for trend aggregation', type: 'string', example: '6m')]
    #[Response(status: 200, description: 'Enrollments trend by month', examples: [
        'success' => true,
        'data' => [
            ['month' => 'Sep', 'value' => 2450],
            ['month' => 'Oct', 'value' => 2580],
        ],
    ])]
    public function enrollmentsTrend(EnrollmentsTrendRequest $request): JsonResponse
    {
        $filters = $request->filters();

        $query = Enrollment::query();
        $this->filters->applyEnrollmentFilters($query, $filters);

        $bounds = $this->filters->periodBounds($request->period());
        $query->whereDate('enrollment_date', '>=', $bounds['start']->toDateString())
            ->whereDate('enrollment_date', '<=', $bounds['end']->toDateString());

        $yearMonthSql = $this->filters->yearMonthSql($query->getConnection(), 'enrollment_date');

        /** @var array<string, int> $aggregated */
        $aggregated = $query
            ->selectRaw("{$yearMonthSql} as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        $data = [];
        foreach ($this->filters->monthBuckets($bounds['months']) as $bucket => $label) {
            $data[] = [
                'month' => $label,
                'value' => $aggregated[$bucket] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    #[Endpoint(operationId: 'dashboardValidationRate')]
    #[Response(status: 200, description: 'Validation gauge metrics', examples: [
        'success' => true,
        'data' => [
            'validated_percent' => 82,
            'failed_percent' => 18,
            'validated_count' => 820,
            'failed_count' => 180,
        ],
    ])]
    public function validationRate(DashboardFiltersRequest $request): JsonResponse
    {
        $filters = $request->filters();

        $query = DeliberationResult::query()
            ->whereHas('deliberationSession', function ($deliberationQuery) use ($filters): void {
                $this->filters->applyDeliberationFilters($deliberationQuery, $filters);
            });

        $validatedDecisions = [
            DeliberationResult::DECISION_ADMITTED,
            DeliberationResult::DECISION_ADMITTED_COMPENSATION,
        ];

        $validatedCount = (clone $query)
            ->whereIn('decision', $validatedDecisions)
            ->count();

        $failedCount = (clone $query)
            ->whereNotIn('decision', $validatedDecisions)
            ->count();

        $total = $validatedCount + $failedCount;
        $validatedPercent = $total > 0 ? (int) round(($validatedCount * 100) / $total) : 0;
        $failedPercent = $total > 0 ? 100 - $validatedPercent : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'validated_percent' => $validatedPercent,
                'failed_percent' => $failedPercent,
                'validated_count' => $validatedCount,
                'failed_count' => $failedCount,
            ],
        ]);
    }

    #[Endpoint(operationId: 'dashboardRecentActivities')]
    #[QueryParameter('limit', 'Maximum number of items', type: 'integer', example: 10)]
    #[Response(status: 200, description: 'Recent activities feed', examples: [
        'success' => true,
        'data' => [
            [
                'id' => 'enrollment_evt_1',
                'name' => 'Sokhna Anta',
                'action' => 'Inscription',
                'context' => 'Mathématiques L3',
                'occurred_at' => '2026-02-12T14:00:00Z',
                'status' => 'VALIDATED',
            ],
        ],
    ])]
    public function recentActivities(RecentActivitiesRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $limit = $request->limit();

        $enrollmentActivities = $this->fetchEnrollmentActivities($filters, $limit * 2);
        $deliberationActivities = $this->fetchDeliberationActivities($filters, $limit * 2);

        $activities = $enrollmentActivities
            ->merge($deliberationActivities)
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchEnrollmentActivities(array $filters, int $limit): Collection
    {
        $query = Enrollment::query()
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('academic_programs', 'academic_programs.id', '=', 'enrollments.academic_program_id')
            ->select([
                'enrollments.id',
                'students.full_name as student_name',
                'academic_programs.name as programme_name',
                'enrollments.current_semester',
                'enrollments.status',
                'enrollments.created_at as occurred_at',
            ]);

        $this->filters->applyEnrollmentFilters($query, $filters);

        return $query
            ->orderByDesc('enrollments.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row): array {
                $semester = (int) $row->current_semester;
                $level = $semester > 0 ? (string) ceil($semester / 2) : '';

                return [
                    'id' => 'enrollment_'.$row->id,
                    'name' => (string) $row->student_name,
                    'action' => 'Inscription',
                    'context' => trim((string) $row->programme_name.($level !== '' ? ' L'.$level : '')),
                    'occurred_at' => $this->iso8601((string) $row->occurred_at),
                    'status' => $this->mapEnrollmentActivityStatus($row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status),
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchDeliberationActivities(array $filters, int $limit): Collection
    {
        $query = DeliberationSession::query()
            ->join('academic_programs', 'academic_programs.id', '=', 'deliberation_sessions.academic_program_id')
            ->leftJoin('faculty_members', 'faculty_members.id', '=', 'deliberation_sessions.presided_by')
            ->select([
                'deliberation_sessions.id',
                'deliberation_sessions.session_name',
                'deliberation_sessions.semester',
                'deliberation_sessions.status',
                'deliberation_sessions.created_at as occurred_at',
                'academic_programs.name as programme_name',
                'faculty_members.full_name as president_name',
            ]);

        $this->filters->applyDeliberationFilters($query, $filters);

        return $query
            ->orderByDesc('deliberation_sessions.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => 'deliberation_'.$row->id,
                    'name' => (string) ($row->president_name ?: $row->session_name),
                    'action' => 'Deliberation',
                    'context' => sprintf('%s S%d', (string) $row->programme_name, (int) $row->semester),
                    'occurred_at' => $this->iso8601((string) $row->occurred_at),
                    'status' => $this->mapDeliberationActivityStatus((string) $row->status),
                ];
            });
    }

    private function resolveLevelBucket(string $programLevel, int $semester): ?string
    {
        if ($programLevel === 'LICENCE') {
            $year = max(1, min(3, (int) ceil(max($semester, 1) / 2)));

            return 'L'.$year;
        }

        if ($programLevel === 'MASTER') {
            $year = max(1, min(2, (int) ceil(max($semester, 1) / 2)));

            return 'M'.$year;
        }

        return null;
    }

    private function mapEnrollmentActivityStatus(string $status): string
    {
        return match ($status) {
            RegistrationStatus::VALIDATED->value => 'VALIDATED',
            RegistrationStatus::PENDING_VALIDATION->value => 'PENDING',
            default => 'PROCESSED',
        };
    }

    private function mapDeliberationActivityStatus(string $status): string
    {
        return match ($status) {
            DeliberationSession::STATUS_COMPLETED,
            DeliberationSession::STATUS_CLOSED => 'VALIDATED',
            DeliberationSession::STATUS_SCHEDULED => 'PENDING',
            default => 'PROCESSED',
        };
    }

    private function iso8601(string $value): string
    {
        return CarbonImmutable::parse($value)->utc()->format('Y-m-d\\TH:i:s\\Z');
    }
}
