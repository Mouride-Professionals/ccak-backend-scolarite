<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        if ($data instanceof LengthAwarePaginator) {
            $payload = [
                'success' => true,
                'data' => [
                    'data' => $data->items(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
                'message' => $message,
            ];
        } else {
            $payload = [
                'success' => true,
                'data' => $data,
                'message' => $message,
            ];
        }

        return response()->json($payload, $status);
    }

    /** @param array<string, mixed> $errors */
    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
