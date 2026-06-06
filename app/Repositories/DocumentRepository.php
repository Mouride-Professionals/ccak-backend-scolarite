<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository
{
    /** @return LengthAwarePaginator<int, Document> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Document::query()->latest('id')->paginate($perPage);
    }

    /** @return Collection<int, Document> */
    public function all(): Collection
    {
        return Document::query()->latest('id')->get();
    }

    public function find(int|string $id): Document
    {
        return Document::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Document
    {
        return Document::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): Document
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
