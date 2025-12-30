<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Enrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentRepository
{
    /** @return LengthAwarePaginator<int, Enrollment> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, Enrollment> */
    public function all(): Collection
    {
        return Enrollment::query()->latest('id')->get();
    }

    public function find(int|string $id): Enrollment
    {
        return Enrollment::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Enrollment
    {
        return Enrollment::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): Enrollment
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
}
