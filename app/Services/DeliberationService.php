<?php

namespace App\Services;

use App\Http\Resources\Academic\DeliberationSessionResource;
use App\Models\DeliberationResult;
use App\Models\DeliberationSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DeliberationService
{
    public function getAll(Request $request)
    {
        $sessions = QueryBuilder::for(DeliberationSession::query())
            ->with(['academicProgram', 'academicYear', 'president', 'juryMembers'])
            ->allowedIncludes(['academicProgram', 'academicYear', 'president', 'juryMembers', 'results'])
            ->allowedFilters([
            AllowedFilter::exact('academic_program_id'),
            AllowedFilter::exact('academic_year_id'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('semester'),
            ])
            ->allowedSorts(['session_date', 'created_at'])
            ->defaultSort('-session_date')
            ->paginate($request->input('per_page', 15));

        return DeliberationSessionResource::collection($sessions);
    }

    public function getById($id)
    {
        return DeliberationSession::with(['academicProgram', 'academicYear', 'president', 'results'])
            ->find($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Créer la session
            $session = DeliberationSession::create([
                'academic_program_id' => $data['academic_program_id'],
                'academic_year_id' => $data['academic_year_id'],
                'semester' => $data['semester'],
                'session_name' => $data['session_name'],
                'session_date' => $data['session_date'],
                'status' => $data['status'] ?? DeliberationSession::STATUS_SCHEDULED,
                'presided_by' => $data['presided_by'],
                'jury_members' => $data['jury_members'] ?? null, // JSON array
            ]);

            return $session->fresh(['academicProgram', 'academicYear', 'president']);
        });
    }

    public function update($id, array $data)
    {
        $session = DeliberationSession::find($id);

        if (!$session) {
            return null;
        }

        return DB::transaction(function () use ($session, $data) {
            $juryMembers = $data['jury_members'] ?? null;
            unset($data['jury_members']);

            $session->update(array_filter($data));

            if ($juryMembers !== null) {
                $session->juryMembers()->sync($juryMembers);
            }

            return $session->load(['academicProgram', 'academicYear', 'president', 'juryMembers']);
        });
    }

    public function delete($id)
    {
        $session = DeliberationSession::find($id);

        if (!$session) {
            return false;
        }

        return $session->delete();
    }

    public function changeStatus($id, $status)
    {
        $session = DeliberationSession::find($id);

        if (!$session) {
            return null;
        }

        $validTransitions = [
            'SCHEDULED' => ['IN_PROGRESS', 'COMPLETED'],
            'IN_PROGRESS' => ['COMPLETED'],
            'COMPLETED' => ['CLOSED'],
            'CLOSED' => []
        ];

        if (!isset($validTransitions[$session->status]) ||
            !in_array($status, $validTransitions[$session->status])) {
            return null;
        }

        $session->update(['status' => $status]);

        return $session->load(['academicProgram', 'academicYear', 'president']);
    }


    /**
     * Fetch eligible students for a deliberation session
     */
    public function fetchEligibleStudents(DeliberationSession $session): Collection
    {
        // TODO: Implémenter la logique pour récupérer les étudiants éligibles
        // Basé sur academic_program_id, academic_year_id, semester
        return collect([]);
    }

    /**
     * Apply decision rules based on student results
     */
    public function applyDecisionRules(array $studentResults): string
    {
        // TODO: Implémenter les règles de décision
        // Exemple: moyenne >= 10 -> ADMITTED
        return DeliberationResult::DECISION_ADMITTED;
    }

    /**
     * Check if student qualifies for compensation
     */
    public function checkCompensation(array $studentResults): bool
    {
        // TODO: Implémenter la logique de compensation
        return false;
    }

    /**
     * Determine honor level based on average
     */
    public function determineHonors(float $average): ?string
    {
        if ($average >= 16) return DeliberationResult::HONOR_LEVEL_TRES_BIEN;
        if ($average >= 14) return DeliberationResult::HONOR_LEVEL_BIEN;
        if ($average >= 12) return DeliberationResult::HONOR_LEVEL_ASSEZ_BIEN;
        if ($average >= 10) return DeliberationResult::HONOR_LEVEL_PASSABLE;

        return null;
    }

    /**
     * Calculate student progression
     */
    public function calculateProgression(string $studentId, DeliberationSession $session): array
    {
        // TODO: Calculer la progression de l'étudiant
        return [
            'can_progress' => true,
            'next_level' => 'L2',
            'conditions' => [],
        ];
    }

    /**
     * Generate recommendations for jury
     */
    public function generateRecommendations(string $studentId, DeliberationSession $session): array
    {
        // TODO: Générer des recommandations
        return [
            'suggested_decision' => DeliberationResult::DECISION_ADMITTED,
            'reasons' => [],
            'warnings' => [],
        ];
    }

    /**
     * Start a deliberation session
     */
    public function startDeliberation(DeliberationSession $session): array
    {
        DB::beginTransaction();

        try {
            // Change status
            $session->update(['status' => DeliberationSession::STATUS_IN_PROGRESS]);

            // Fetch eligible students
            $students = $this->fetchEligibleStudents($session);

            // Create result placeholders
            foreach ($students as $student) {
                DeliberationResult::firstOrCreate([
                    'deliberation_session_id' => $session->id,
                    'student_id' => $student['id'],
                ], [
                    'decision' => DeliberationResult::DECISION_RESIT, // Valeur par défaut
                    'is_with_honors' => false,
                ]);
            }

            DB::commit();

            return [
                'session' => $session->fresh(),
                'students' => $students,
                'recommendations' => $students->map(fn($s) =>
                    $this->generateRecommendations($s['id'], $session)
                ),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete a deliberation session
     */
    public function completeDeliberation(DeliberationSession $session): bool
    {
        // Check all decisions are set
        $pendingResults = $session->results()
            ->whereNull('decision')
            ->orWhere('decision', '')
            ->count();

        if ($pendingResults > 0) {
            throw new \Exception("All student decisions must be set before completing");
        }

        DB::beginTransaction();

        try {
            $session->update(['status' => DeliberationSession::STATUS_COMPLETED]);

            // TODO: Trigger notifications
            // TODO: Generate minutes

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
