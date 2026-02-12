# Dashboard KPI Formulas

This document defines deterministic formulas used by dashboard endpoints.

## Global Filters

Supported filters (when applicable):

- `academic_year_id` (UUID)
- `semester` (integer)
- `date_from` (YYYY-MM-DD)
- `date_to` (YYYY-MM-DD)
- `department_id` (UUID)
- `programme_id` (UUID)
- `faculty_id` (UUID)

Filter mapping:

- Enrollment-based metrics: `enrollments` table filtered by `academic_year_id`, `current_semester`, `academic_program_id`, `enrollment_date`, and (for department/faculty) `academic_programs -> departments`.
- Deliberation-based metrics: `deliberation_sessions` table filtered by `academic_year_id`, `semester`, `academic_program_id`, `session_date`, and (for department/faculty) `academic_programs -> departments`.

## `/v1/dashboard/overview`

- `total_students`: `COUNT(DISTINCT enrollments.student_id)` after enrollment filters.
- `total_enrollments`: `COUNT(enrollments.id)` after enrollment filters.
- `total_deliberations`: `COUNT(deliberation_sessions.id)` after deliberation filters.
- `pending_items`: `pending_enrollments + pending_deliberations`
  - `pending_enrollments`: enrollments with `status = PENDING` after enrollment filters.
  - `pending_deliberations`: deliberation sessions with `status IN (SCHEDULED, IN_PROGRESS)` after deliberation filters.

## `/v1/dashboard/students-by-level`

Built from filtered enrollments joined to academic programs:

- Program level `LICENCE`:
  - semester `1..2` => `L1`
  - semester `3..4` => `L2`
  - semester `5..6` => `L3`
- Program level `MASTER`:
  - semester `1..2` => `M1`
  - semester `3..4` => `M2`

Per bucket, value is `COUNT(DISTINCT enrollments.student_id)`.

## `/v1/dashboard/enrollments-trend`

- Uses filtered enrollments.
- Additional `period` window (`6m`, `12m`, `24m`, default `6m`) applied on `enrollment_date`.
- Grouping key is calendar month (`YYYY-MM`).
- Output points are month labels (`Jan`, `Feb`, etc.) with `value = COUNT(*)` for each month in window; missing months return `0`.

## `/v1/dashboard/validation-rate`

Built from `deliberation_results` filtered through parent `deliberation_sessions` filters.

- `validated_count`: decisions in `ADMITTED`, `ADMITTED_COMPENSATION`.
- `failed_count`: decisions not in validated set.
- `validated_percent`: `ROUND(validated_count * 100 / total)` where `total = validated_count + failed_count`.
- `failed_percent`: `100 - validated_percent` when `total > 0`, otherwise `0`.

## `/v1/dashboard/recent-activities`

Feed is a merged, date-desc sorted stream of:

- Enrollment events (`action = Inscription`, source `enrollments`).
- Deliberation events (`action = Deliberation`, source `deliberation_sessions`).

Canonical status mapping:

- `VALIDATED`
- `PROCESSED`
- `PENDING`

## `/v1/enrollments/dashboard`

- `kpis.total_enrollments`: all filtered enrollments.
- `kpis.active_enrollments`: filtered enrollments with status in `ACTIVE`, `REGISTERED`.
- `kpis.pending_enrollments`: filtered enrollments with status `PENDING`.
- `kpis.completed_enrollments`: filtered enrollments with status `COMPLETED`.
- `kpis.withdrawn_enrollments`: filtered enrollments with status `WITHDRAWN`.
- `trend`: same monthly logic as `/v1/dashboard/enrollments-trend`, field name `count`.
- `program_distribution`: grouped by academic program name over filtered enrollments.
- `recent_enrollments`: latest filtered enrollments ordered by `created_at DESC`.
