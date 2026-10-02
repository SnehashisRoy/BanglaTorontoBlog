<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $url = Fortify::redirects('logout', '/');

        // The in-app "Log out" button lives on Inertia pages (dashboard,
        // settings) and posts via Inertia, but the destination (home) is a
        // plain Blade view. See App\Http\Responses\LoginResponse for why this
        // isn't a plain redirect.
        return Inertia::location($url);
    }
}
