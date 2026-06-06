<?php

namespace App\Services;

use App\Models\DeliberationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliberationResultService
{
    /**
     * Get all deliberation results
     *
     * @return Collection<int, DeliberationResult>
     */
    public function getAll(): Collection
    {
        return DeliberationResult::with(['deliberationSession'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get deliberation result by ID
     */
    public function getById(string $id): ?DeliberationResult
    {
        return DeliberationResult::with(['deliberationSession'])
            ->find($id);
    }

    /**
     * Create a new deliberation result
     */
    /** @param array<string, mixed> $data */
    public function create(array $data): DeliberationResult
    {
        return DeliberationResult::create($data);
    }

    /**
     * Update a deliberation result
     */
    /** @param array<string, mixed> $data */
    public function update(DeliberationResult $result, array $data): DeliberationResult
    {
        $result->update($data);

        return $result->refresh();
    }

    /**
     * Delete a deliberation result
     */
    public function delete(DeliberationResult $result): bool
    {
        return $result->delete();
    }

    /**
     * Get results by session
     *
     * @return Collection<int, DeliberationResult>
     */
    public function getBySession(string $sessionId): Collection
    {
        return DeliberationResult::where('deliberation_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get results by student
     *
     * @return Collection<int, DeliberationResult>
     */
    public function getByStudent(string $studentId): Collection
    {
        return DeliberationResult::where('student_id', $studentId)
            ->with(['deliberationSession'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /** @param array<int, array<string, mixed>> $results */
    public function bulkCreate(array $results): bool
    {
        return DB::transaction(function () use ($results) {
            return DeliberationResult::insert($results);
        });
    }
}
