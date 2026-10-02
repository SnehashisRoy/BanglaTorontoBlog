<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
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
            return new JsonResponse('', 201);
        }

        $url = redirect()->intended(Fortify::redirects('register'))->getTargetUrl();

        // The register form is an Inertia page, but the destination may be a
        // plain Blade view (e.g. /vendor/register, when registration was
        // reached via the "sell with us" intent). See
        // App\Http\Responses\LoginResponse for why this isn't a plain redirect.
        return Inertia::location($url);
    }
}
