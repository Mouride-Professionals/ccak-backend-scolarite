<?php

namespace App\Services;

use App\Models\DeliberationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliberationResultService
{
    /**
     * Get all deliberation results
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
    public function create(array $data): DeliberationResult
    {
        return DeliberationResult::create($data);
    }

    /**
     * Update a deliberation result
     */
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
     */
    public function getBySession(string $sessionId): Collection
    {
        return DeliberationResult::where('deliberation_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get results by student
     */
    public function getByStudent(string $studentId): Collection
    {
        return DeliberationResult::where('student_id', $studentId)
            ->with(['deliberationSession'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function bulkCreate(array $results)
    {
        return DB::transaction(function () use ($results) {
            return DeliberationResult::insert($results);
        });
    }
}
