<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Carbon;

trait UsesSenegalAcademicCalendar
{
    protected function currentAcademicYearName(): string
    {
        $now = Carbon::now('Africa/Dakar');
        $startYear = $now->month >= 10 ? $now->year : $now->year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function parseAcademicYear(string $name): array
    {
        if (preg_match('/^(\\d{4})-(\\d{4})$/', $name, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        $year = (int) Carbon::now('Africa/Dakar')->year;

        return [$year, $year + 1];
    }

    protected function academicYearStart(string $name): Carbon
    {
        [$startYear] = $this->parseAcademicYear($name);

        return Carbon::create($startYear, 10, 1, 0, 0, 0, 'Africa/Dakar');
    }

    protected function academicYearEnd(string $name): Carbon
    {
        [$startYear] = $this->parseAcademicYear($name);

        return Carbon::create($startYear + 1, 7, 31, 23, 59, 59, 'Africa/Dakar');
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function enrollmentWindow(string $name): array
    {
        $start = $this->academicYearStart($name)->copy()->addWeeks(1);
        $end = $this->academicYearStart($name)->copy()->addMonths(2)->endOfDay();

        return [$start, $end];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function semesterWindow(string $name, int $semester): array
    {
        $start = $this->academicYearStart($name);

        if ($semester === 1) {
            return [
                $start->copy(),
                Carbon::create($start->year + 1, 2, 15, 23, 59, 59, 'Africa/Dakar'),
            ];
        }

        return [
            Carbon::create($start->year + 1, 3, 1, 0, 0, 0, 'Africa/Dakar'),
            Carbon::create($start->year + 1, 7, 31, 23, 59, 59, 'Africa/Dakar'),
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function examWindow(string $name, int $semester): array
    {
        $start = $this->academicYearStart($name);

        if ($semester === 1) {
            return [
                Carbon::create($start->year + 1, 1, 15, 0, 0, 0, 'Africa/Dakar'),
                Carbon::create($start->year + 1, 2, 15, 23, 59, 59, 'Africa/Dakar'),
            ];
        }

        return [
            Carbon::create($start->year + 1, 6, 1, 0, 0, 0, 'Africa/Dakar'),
            Carbon::create($start->year + 1, 7, 15, 23, 59, 59, 'Africa/Dakar'),
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function deliberationWindow(string $name, int $semester): array
    {
        $start = $this->academicYearStart($name);

        if ($semester === 1) {
            return [
                Carbon::create($start->year + 1, 2, 20, 0, 0, 0, 'Africa/Dakar'),
                Carbon::create($start->year + 1, 3, 5, 23, 59, 59, 'Africa/Dakar'),
            ];
        }

        return [
            Carbon::create($start->year + 1, 7, 16, 0, 0, 0, 'Africa/Dakar'),
            Carbon::create($start->year + 1, 7, 31, 23, 59, 59, 'Africa/Dakar'),
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function documentIssuanceWindow(string $name): array
    {
        $end = $this->academicYearEnd($name);

        return [
            $end->copy()->subWeeks(6)->startOfDay(),
            $end->copy()->endOfDay(),
        ];
    }
}
