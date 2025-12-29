<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\CourseEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CourseEnrollmentRepository
{
    /** @return LengthAwarePaginator<int, CourseEnrollment> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CourseEnrollment::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, CourseEnrollment> */
    public function all(): Collection
    {
        return CourseEnrollment::query()->latest('id')->get();
    }

    public function find(int|string $id): CourseEnrollment
    {
        return CourseEnrollment::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CourseEnrollment
    {
        return CourseEnrollment::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): CourseEnrollment
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
