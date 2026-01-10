<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        $meta = null;

        if ($data instanceof ResourceCollection) {
            $resource = $data->resource;
            if ($resource instanceof AbstractPaginator) {
                $data = $resource->toArray();
            } else {
                $data = $data->resolve();
            }
        } elseif ($data instanceof AbstractPaginator) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonResource) {
            $data = $data->resolve();
        } elseif ($data instanceof Collection) {
            $values = $data->values()->all();
            $data = [
                'data' => $values,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => count($values),
                'total' => count($values),
            ];
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
