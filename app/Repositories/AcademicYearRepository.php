<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\AcademicYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AcademicYearRepository
{
    /** @return LengthAwarePaginator<int, AcademicYear> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return AcademicYear::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, AcademicYear> */
    public function all(): Collection
    {
        return AcademicYear::query()->latest('id')->get();
    }

    public function find(int|string $id): AcademicYear
    {
        return AcademicYear::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): AcademicYear
    {
        return AcademicYear::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): AcademicYear
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
