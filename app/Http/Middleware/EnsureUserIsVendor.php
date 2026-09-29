<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->vendor) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}
