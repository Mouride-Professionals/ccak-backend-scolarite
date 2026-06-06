<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->sanitizeArray($request->all()));

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);

                continue;
            }

            if (is_string($value)) {
                $data[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }
}
