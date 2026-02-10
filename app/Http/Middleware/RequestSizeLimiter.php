<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestSizeLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxBytes = (int) config('http.max_request_size', 10 * 1024 * 1024);
        $length = (int) $request->server('CONTENT_LENGTH', 0);

        if ($length > 0 && $length > $maxBytes) {
            return response()->json([
                'success' => false,
                'message' => 'Payload too large.',
                'errors' => [],
            ], 413);
        }

        return $next($request);
    }
}
