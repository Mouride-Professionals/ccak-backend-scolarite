<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StudentRepository
{
    /** @return LengthAwarePaginator<int, Student> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Student::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, Student> */
    public function all(): Collection
    {
        return Student::query()->latest('id')->get();
    }

    public function find(int|string $id): Student
    {
        return Student::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Student
    {
        return Student::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): Student
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
