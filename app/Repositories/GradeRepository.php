<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Grade;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GradeRepository
{
    /** @return LengthAwarePaginator<int, Grade> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Grade::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, Grade> */
    public function all(): Collection
    {
        return Grade::query()->latest('id')->get();
    }

    public function find(int|string $id): Grade
    {
        return Grade::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Grade
    {
        return Grade::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): Grade
    {
        $item = $this->find($id);
        $item->update($data);

        return $item;
    }

    public function delete(int|string $id): void
    {
        $item = $this->find($id);
        $item->delete();
    }

    /**
     * Get all grades by status
     *
     * @return Collection<int, Grade>
     */
    public function getByStatus(string $status): Collection
    {
        return Grade::query()->where('status', $status)->get();
    }

    /**
     * Bulk publish grades by IDs
     *
     * @param  array<int|string>  $ids
     * @return int Number of grades updated
     */
    public function bulkPublish(array $ids): int
    {
        return Grade::query()
            ->whereIn('id', $ids)
            ->where('status', 'VALIDATED')
            ->update(['status' => 'PUBLISHED']);
    }
}
