<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        $meta = null;

        if ($data instanceof AbstractPaginator) {
            $meta = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
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
