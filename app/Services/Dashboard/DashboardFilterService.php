<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;

class DashboardFilterService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function applyEnrollmentFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['semester'])) {
            $query->where('current_semester', (int) $filters['semester']);
        }

        if (isset($filters['programme_id'])) {
            $query->where('academic_program_id', $filters['programme_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('enrollment_date', '>=', (string) $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('enrollment_date', '<=', (string) $filters['date_to']);
        }

        if (isset($filters['department_id']) || isset($filters['faculty_id'])) {
            $query->whereHas('academicProgram.department', function (Builder $departmentQuery) use ($filters): void {
                if (isset($filters['department_id'])) {
                    $departmentQuery->where('departments.id', $filters['department_id']);
                }

                if (isset($filters['faculty_id'])) {
                    $departmentQuery->where('departments.faculty_id', $filters['faculty_id']);
                }
            });
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function applyDeliberationFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['semester'])) {
            $query->where('semester', (int) $filters['semester']);
        }

        if (isset($filters['programme_id'])) {
            $query->where('academic_program_id', $filters['programme_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('session_date', '>=', (string) $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('session_date', '<=', (string) $filters['date_to']);
        }

        if (isset($filters['department_id']) || isset($filters['faculty_id'])) {
            $query->whereHas('academicProgram.department', function (Builder $departmentQuery) use ($filters): void {
                if (isset($filters['department_id'])) {
                    $departmentQuery->where('departments.id', $filters['department_id']);
                }

                if (isset($filters['faculty_id'])) {
                    $departmentQuery->where('departments.faculty_id', $filters['faculty_id']);
                }
            });
        }

        return $query;
    }

    /**
     * @return array{months: int, start: CarbonImmutable, end: CarbonImmutable}
     */
    public function periodBounds(string $period): array
    {
        $months = in_array($period, ['6m', '12m', '24m'], true)
            ? (int) rtrim($period, 'm')
            : 6;

        $end = CarbonImmutable::now()->endOfMonth();
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        return [
            'months' => $months,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function monthBuckets(int $months): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);
        $buckets = [];

        for ($index = 0; $index < $months; $index++) {
            $month = $start->addMonths($index);
            $buckets[$month->format('Y-m')] = $month->format('M');
        }

        return $buckets;
    }

    public function yearMonthSql(ConnectionInterface $connection, string $column): string
    {
        return match ($connection->getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlite' => "STRFTIME('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
