<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\EnrollmentsDashboardRequest;
use App\Models\Enrollment;
use App\Services\Dashboard\DashboardFilterService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group('Dashboard', 'Enrollment dashboard aggregate endpoint.')]
class EnrollmentDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardFilterService $filters,
    ) {}

    #[Endpoint(operationId: 'enrollmentsDashboard')]
    #[QueryParameter('period', 'Window for trend aggregation', type: 'string', example: '6m')]
    #[QueryParameter('recent_limit', 'Maximum items in recent enrollments list', type: 'integer', example: 10)]
    #[Response(status: 200, description: 'Enrollment dashboard payload', examples: [
        'success' => true,
        'data' => [
            'kpis' => [
                'total_enrollments' => 0,
                'active_enrollments' => 0,
                'pending_enrollments' => 0,
                'completed_enrollments' => 0,
                'withdrawn_enrollments' => 0,
            ],
            'trend' => [
                ['month' => 'Sep', 'count' => 0],
            ],
            'program_distribution' => [
                ['name' => 'Licence Info', 'count' => 0],
            ],
            'recent_enrollments' => [],
        ],
    ])]
    public function index(EnrollmentsDashboardRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $query = Enrollment::query();
        $this->filters->applyEnrollmentFilters($query, $filters);

        $kpis = [
            'total_enrollments' => (clone $query)->count(),
            'active_enrollments' => (clone $query)
                ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_REGISTERED])
                ->count(),
            'pending_enrollments' => (clone $query)
                ->where('status', Enrollment::STATUS_PENDING)
                ->count(),
            'completed_enrollments' => (clone $query)
                ->where('status', Enrollment::STATUS_COMPLETED)
                ->count(),
            'withdrawn_enrollments' => (clone $query)
                ->where('status', Enrollment::STATUS_WITHDRAWN)
                ->count(),
        ];

        $bounds = $this->filters->periodBounds($request->period());
        $trendQuery = clone $query;
        $trendQuery
            ->whereDate('enrollment_date', '>=', $bounds['start']->toDateString())
            ->whereDate('enrollment_date', '<=', $bounds['end']->toDateString());

        $yearMonthSql = $this->filters->yearMonthSql($trendQuery->getConnection(), 'enrollment_date');

        /** @var array<string, int> $trendRows */
        $trendRows = $trendQuery
            ->selectRaw("{$yearMonthSql} as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        $trend = [];
        foreach ($this->filters->monthBuckets($bounds['months']) as $bucket => $label) {
            $trend[] = [
                'month' => $label,
                'count' => $trendRows[$bucket] ?? 0,
            ];
        }

        $distributionQuery = clone $query;
        $programDistribution = $distributionQuery
            ->join('academic_programs', 'academic_programs.id', '=', 'enrollments.academic_program_id')
            ->selectRaw('academic_programs.name as name, COUNT(*) as total')
            ->groupBy('academic_programs.name')
            ->orderByDesc('total')
            ->get()
            ->map(static fn ($row): array => [
                'name' => (string) $row->name,
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();

        $recentQuery = clone $query;
        $recentEnrollments = $recentQuery
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('academic_programs', 'academic_programs.id', '=', 'enrollments.academic_program_id')
            ->select([
                'enrollments.id',
                'students.full_name as student_name',
                'academic_programs.name as programme_name',
                'enrollments.status',
                'enrollments.enrollment_date',
                'enrollments.created_at',
            ])
            ->orderByDesc('enrollments.created_at')
            ->limit($request->recentLimit())
            ->get()
            ->map(static fn ($row): array => [
                'id' => (string) $row->id,
                'student_name' => (string) $row->student_name,
                'programme_name' => (string) $row->programme_name,
                'status' => (string) $row->status,
                'enrollment_date' => CarbonImmutable::parse((string) $row->enrollment_date)->toDateString(),
                'created_at' => CarbonImmutable::parse((string) $row->created_at)->utc()->format('Y-m-d\\TH:i:s\\Z'),
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'trend' => $trend,
                'program_distribution' => $programDistribution,
                'recent_enrollments' => $recentEnrollments,
            ],
        ]);
    }
}
