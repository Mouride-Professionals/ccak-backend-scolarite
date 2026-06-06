<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        $meta = null;

        if ($data instanceof ResourceCollection) {
            $resource = $data->resource;
            if ($resource instanceof LengthAwarePaginator) {
                $resolved = $data->resolve();
                $data = is_array($resolved) && array_key_exists('data', $resolved)
                    ? $resolved['data']
                    : $resolved;
                $meta = [
                    'current_page' => $resource->currentPage(),
                    'per_page' => $resource->perPage(),
                    'total' => $resource->total(),
                    'last_page' => $resource->lastPage(),
                ];
            } else {
                $data = $data->resolve();
            }
        } elseif ($data instanceof LengthAwarePaginator) {
            $meta = [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
            $data = $data->items();
        } elseif ($data instanceof JsonResource) {
            $data = $data->resolve();
        } elseif ($data instanceof Collection) {
            $data = $data->values()->all();
        }

        if ($meta === null && is_array($data) && array_is_list($data)) {
            $meta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => count($data),
                'total' => count($data),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
